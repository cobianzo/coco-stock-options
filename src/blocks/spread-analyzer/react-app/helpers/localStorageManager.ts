/**
 * Manages storing and retrieving data from localStorage.
 */

const STRIKE_BUY_KEY = 'strikeBuy';
const STRIKE_SELL_KEY = 'strikeSell';

/**
 * Creates a standardized key for localStorage.
 * @param ticker - The stock ticker.
 * @param key - The specific key to use.
 * @returns A string in the format 'ticker_key'.
 */
const getKey = (ticker: string, key: string): string => `${ticker}_${key}`;

/**
 * Saves a strike value to localStorage.
 *
 * @param ticker The stock ticker symbol.
 * @param strikeType The type of strike ('strikeBuy' or 'strikeSell').
 * @param value The value to save.
 */
export const saveStrike = (
	ticker: string,
	strikeType: 'strikeBuy' | 'strikeSell',
	value: number | string
): void => {

	if (!ticker) return;
	try {
		const key = getKey(
			ticker,
			strikeType === 'strikeBuy' ? STRIKE_BUY_KEY : STRIKE_SELL_KEY
		);
		localStorage.setItem(key, String(value));
	} catch (error) {
		console.error('Error saving to localStorage:', error);
	}
};

/**
 * Loads a strike value from localStorage.
 *
 * @param ticker The stock ticker symbol.
 * @param strikeType The type of strike ('strikeBuy' or 'strikeSell').
 * @returns The loaded value as a string, or null if not found or an error occurs.
 */
export const loadStrike = (
	ticker: string,
	strikeType: 'strikeBuy' | 'strikeSell'
): number | null => {
	if (!ticker) return null;
	try {
		const key = getKey(
			ticker,
			strikeType === 'strikeBuy' ? STRIKE_BUY_KEY : STRIKE_SELL_KEY
		);
		return parseFloat( localStorage.getItem(key) || '0' );
	} catch (error) {
		console.error('Error loading from localStorage:', error);
		return null;
	}
};