export interface MaskRule {
  column: string;
  mask_type: 'full_redact' | 'partial_mask';
}

export interface SecurityPolicyConfig {
  whitelistTables: string[];
  maskedColumns: MaskRule[];
  maxQueryLimit: number;
  isActive: boolean;
}

export interface ValidationResult {
  valid: boolean;
  reason?: string;
  sanitizedQuery?: string;
}

const MUTATION_KEYWORDS = [
  'INSERT',
  'UPDATE',
  'DELETE',
  'DROP',
  'ALTER',
  'TRUNCATE',
  'CREATE',
  'REPLACE',
  'GRANT',
  'REVOKE',
  'EXEC',
  'EXECUTE',
  'MERGE',
  'UPSERT',
  'LOCK',
  'CALL'
];

/**
 * Validate and sanitize SQL query against policies
 */
export function validateAndSanitizeQuery(
  rawQuery: string,
  policy: SecurityPolicyConfig
): ValidationResult {
  const trimmed = rawQuery.trim();

  if (!trimmed) {
    return { valid: false, reason: 'Empty query received' };
  }

  // 1. Check for multiple stacked queries separated by semicolons
  // Strip trailing semicolon if single statement
  const queryWithoutTrailingSemicolon = trimmed.replace(/;\s*$/, '');
  if (queryWithoutTrailingSemicolon.includes(';')) {
    return {
      valid: false,
      reason: 'Blocked: Multiple stacked queries separated by semicolons are strictly prohibited'
    };
  }

  // 2. Pure SELECT / CTE check
  const upper = queryWithoutTrailingSemicolon.toUpperCase().replace(/\s+/g, ' ');
  const isSelect = upper.startsWith('SELECT');
  const isCte = upper.startsWith('WITH');

  if (!isSelect && !isCte) {
    return {
      valid: false,
      reason: "Blocked: Only read-only queries ('SELECT' or 'WITH ... SELECT') are permitted"
    };
  }

  // 3. Reject mutation keywords using word boundary regex
  for (const keyword of MUTATION_KEYWORDS) {
    const regex = new RegExp(`\\b${keyword}\\b`, 'i');
    if (regex.test(queryWithoutTrailingSemicolon)) {
      return {
        valid: false,
        reason: `Blocked: Mutation keyword '${keyword}' is strictly forbidden in read-only MCP data gateway`
      };
    }
  }

  // If policy is not active, allow with basic LIMIT injection
  const maxLimit = policy.maxQueryLimit > 0 ? policy.maxQueryLimit : 50;

  // 4. Whitelist Tables Check (if policy active and whitelist is populated)
  if (policy.isActive && policy.whitelistTables && policy.whitelistTables.length > 0) {
    const lowerWhitelisted = policy.whitelistTables.map(t => t.toLowerCase());

    // Extract table names after FROM, JOIN, INTO
    const fromJoinRegex = /\b(?:FROM|JOIN)\s+([a-zA-Z0-9_".]+)/gi;
    let match;
    const referencedTables: string[] = [];

    while ((match = fromJoinRegex.exec(queryWithoutTrailingSemicolon)) !== null) {
      let tableName = match[1].replace(/["`]/g, '').trim();
      // If table is prefixed with schema e.g. public.users
      if (tableName.includes('.')) {
        tableName = tableName.split('.').pop() || tableName;
      }
      referencedTables.push(tableName.toLowerCase());
    }

    for (const refTable of referencedTables) {
      // Ignore subquery aliases or common functions like unnest, json_each
      if (['(', 'values', 'unnest', 'generate_series'].includes(refTable)) {
        continue;
      }

      if (!lowerWhitelisted.includes(refTable)) {
        return {
          valid: false,
          reason: `Blocked: Table '${refTable}' is not in the whitelist for this database policy`
        };
      }
    }
  }

  // 5. Automatic LIMIT Injection / Enforcement
  let sanitizedQuery = queryWithoutTrailingSemicolon;
  const limitRegex = /\bLIMIT\s+(\d+)/i;
  const limitMatch = sanitizedQuery.match(limitRegex);

  if (limitMatch) {
    const existingLimit = parseInt(limitMatch[1], 10);
    if (existingLimit > maxLimit) {
      // Clamp to max limit
      sanitizedQuery = sanitizedQuery.replace(limitRegex, `LIMIT ${maxLimit}`);
    }
  } else {
    // Append LIMIT clause
    sanitizedQuery = `${sanitizedQuery} LIMIT ${maxLimit}`;
  }

  return {
    valid: true,
    sanitizedQuery
  };
}

/**
 * Apply data masking to query result rows
 */
export function applyDataMasking(
  rows: Record<string, any>[],
  rules: MaskRule[]
): { maskedRows: Record<string, any>[]; maskedCount: number } {
  if (!rules || rules.length === 0 || rows.length === 0) {
    return { maskedRows: rows, maskedCount: 0 };
  }

  // Index rules by lowercase column name
  const ruleMap = new Map<string, MaskRule>();
  for (const rule of rules) {
    if (rule.column) {
      ruleMap.set(rule.column.toLowerCase().trim(), rule);
    }
  }

  let totalMaskedCount = 0;

  const maskedRows = rows.map(row => {
    const newRow = { ...row };

    for (const key of Object.keys(newRow)) {
      const rule = ruleMap.get(key.toLowerCase());
      if (rule) {
        totalMaskedCount++;
        const val = newRow[key];

        if (val === null || val === undefined) {
          continue;
        }

        if (rule.mask_type === 'full_redact') {
          newRow[key] = '[REDACTED]';
        } else if (rule.mask_type === 'partial_mask') {
          newRow[key] = maskPartialValue(String(val));
        }
      }
    }

    return newRow;
  });

  return {
    maskedRows,
    maskedCount: totalMaskedCount
  };
}

/**
 * Partial masking helper for emails, phone numbers, credit cards, or text
 */
function maskPartialValue(val: string): string {
  // If email format
  if (val.includes('@')) {
    const [user, domain] = val.split('@');
    if (user.length <= 2) {
      return `*@${domain}`;
    }
    const maskedUser = `${user[0]}***${user[user.length - 1]}`;
    return `${maskedUser}@${domain}`;
  }

  // If short string <= 4 chars
  if (val.length <= 4) {
    return '****';
  }

  // Credit card or long alphanumeric string: keep first 2 and last 4
  const start = val.slice(0, 2);
  const end = val.slice(-4);
  return `${start}••••••••${end}`;
}
