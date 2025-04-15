/**
 * Valida una password secondo i criteri di sicurezza
 * @param {string} password - La password da validare
 * @returns {boolean} - true se la password è valida, false altrimenti
 */
export function validatePassword(password) {
    const minLength = 12;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

    return password.length >= minLength &&
           hasUpperCase &&
           hasLowerCase &&
           hasNumbers &&
           hasSpecialChar;
}

/**
 * Valida un indirizzo email
 * @param {string} email - L'email da validare
 * @returns {boolean} - true se l'email è valida, false altrimenti
 */
export function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Valida un nickname
 * @param {string} nickname - Il nickname da validare
 * @returns {boolean} - true se il nickname è valido, false altrimenti
 */
export function validateNickname(nickname) {
    const minLength = 3;
    const maxLength = 30;
    const validChars = /^[a-zA-Z0-9_-]+$/;

    return nickname.length >= minLength &&
           nickname.length <= maxLength &&
           validChars.test(nickname);
}

/**
 * Valida un nome o cognome
 * @param {string} name - Il nome da validare
 * @returns {boolean} - true se il nome è valido, false altrimenti
 */
export function validateName(name) {
    const minLength = 2;
    const maxLength = 50;
    const validChars = /^[a-zA-ZÀ-ÿ\s'-]+$/;

    return name.length >= minLength &&
           name.length <= maxLength &&
           validChars.test(name);
}

/**
 * Sanitizza una stringa di input
 * @param {string} input - La stringa da sanitizzare
 * @returns {string} - La stringa sanitizzata
 */
export function sanitizeInput(input) {
    const div = document.createElement('div');
    div.textContent = input;
    return div.innerHTML;
} 