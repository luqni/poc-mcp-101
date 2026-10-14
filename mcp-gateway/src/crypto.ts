import crypto from 'crypto';

/**
 * Hash raw token with SHA-256 to compare with api_keys.key_hash
 */
export function hashToken(token: string): string {
  return crypto.createHash('sha256').update(token.trim()).digest('hex');
}

/**
 * Decrypt Laravel's encrypted string (AES-256-CBC)
 */
export function decryptLaravelString(payloadBase64: string, appKey: string): string {
  try {
    if (!payloadBase64 || typeof payloadBase64 !== 'string') {
      return '';
    }

    const rawKey = appKey.startsWith('base64:') ? appKey.slice(7) : appKey;
    const key = Buffer.from(rawKey, 'base64');

    const jsonStr = Buffer.from(payloadBase64, 'base64').toString('utf8');
    const payload = JSON.parse(jsonStr);

    if (!payload.iv || !payload.value) {
      return payloadBase64;
    }

    const iv = Buffer.from(payload.iv, 'base64');
    const ciphertext = Buffer.from(payload.value, 'base64');

    const decipher = crypto.createDecipheriv('aes-256-cbc', key, iv);
    let decrypted = decipher.update(ciphertext, undefined, 'utf8');
    decrypted += decipher.final('utf8');

    // Handle serialized PHP string e.g. s:8:"P@ssw0rd";
    if (decrypted.startsWith('s:') && decrypted.includes(':"')) {
      const match = decrypted.match(/^s:\d+:"(.*)";$/s);
      if (match) {
        return match[1];
      }
    }

    return decrypted;
  } catch (err) {
    // Fallback: If not encrypted or fails decryption, return raw
    return payloadBase64;
  }
}
