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

/**
 * Converts strike price string to number with correct decimals
 * @param strikePrice Strike price string where last 3 digits are decimals (e.g. '00002500')
 * @returns Number with correct decimal places (e.g. 2.500)
 */
export const sanitizeStrikePrice = (strikePrice: string): number => {
    if (!strikePrice || !/^\d+$/.test(strikePrice)) {
        throw new Error('Invalid strike price format');
    }

    let strikeFloat = parseFloat(strikePrice);

    return strikeFloat / 1000;
};

export const extractFormalStrikePrice = (strikePrice: number): string => {
    let formal =  strikePrice.toFixed(3).replace('.', '');
    // Pad with leading zeros if less than 6 digits
    while (formal.length < 8) {
        formal = '0' + formal;
    }
    return formal as string;
}
