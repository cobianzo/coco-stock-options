/**
 * Extracts date from option symbol string and converts it to Date object
 * @param symbol Option symbol string (e.g. 'BXMT250815P')
 * @returns Date object
 */
export const extractDateFromSymbol = (symbol: string): Date => {
    // Extract numbers using regex
    const numbers = symbol.match(/\d+/)?.[0];

    if (!numbers || numbers.length < 6) {
        throw new Error('Invalid symbol format');
    }

    // Extract year, month and day
    const year = parseInt('20' + numbers.substring(0, 2));
    const month = parseInt(numbers.substring(2, 4)) - 1; // JS months are 0-based
    const day = parseInt(numbers.substring(4, 6));

    return new Date(year, month, day);
};
