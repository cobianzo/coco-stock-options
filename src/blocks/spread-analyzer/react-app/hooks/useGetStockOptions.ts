import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

// types
import { WPAllOptionsData } from 'src/types/types';

const useGetStockOptions = (stockId: number, type?: 'put' | 'call') => {
    const [optionsData, setOptionsData] = useState<WPAllOptionsData>({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<Error | null>(null);

    useEffect(() => {
        if (!stockId) {
            setLoading(false);
            setOptionsData({});
            return;
        }

        setLoading(true);
        let path = `/coco/v1/stock-options-by-id/${stockId}`;
        if (type) {
            path += `?type=${type}`;
        }

        apiFetch({ path })
            .then((fetchedOptions) => {
								if (fetchedOptions) {
									setOptionsData(fetchedOptions as WPAllOptionsData);
								} else {
									setError(new Error('error fetching, unknown'));
								}
                setLoading(false);
            })
            .catch((fetchError) => {
                setError(fetchError);
                setLoading(false);
            });
    }, [stockId, type]);

    return { optionsData, loading, error };
};

export default useGetStockOptions;
