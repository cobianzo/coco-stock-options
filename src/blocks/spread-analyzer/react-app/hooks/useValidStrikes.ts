import { useEffect, useState } from 'react';
import { sanitizeStrikePrice } from '../helpers/sanitazors';

/**
 * Custom hook to extract valid strikes from options data
 * @param options Options data object
 * @returns Object with validStrikes array, strikeSell and strikeBuy values
 */
const useValidStrikes = (options: Record<string, any> | null) => {
  const [validStrikes, setValidStrikes] = useState<number[]>([]);

  useEffect(() => {
    if (!options) return;

    // Obtener strikes válidos
    let strikes: number[] = [];
    Object.keys(options).forEach((date6digits) => {
      const strikesForTheDate = options[date6digits];
      strikes = strikes.concat(Object.keys(strikesForTheDate).map((a) => sanitizeStrikePrice(a)));
      strikes.sort((a, b) => a - b);
      strikes = Array.from(new Set(strikes)); // Remove duplicates
    });
    setValidStrikes(strikes);

  }, [options]);

  return { validStrikes };
};

export default useValidStrikes;