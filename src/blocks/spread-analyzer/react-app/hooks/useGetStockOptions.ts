import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const useGetStockOptions = (stockId: number, type?: 'put' | 'call') => {
    const [options, setOptions] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!stockId) {
            setLoading(false);
            setOptions(null);
            return;
        }

        setLoading(true);
        let path = `/coco/v1/stock-options-by-id/${stockId}`;
        if (type) {
            path += `?type=${type}`;
        }

        apiFetch({ path })
            .then((fetchedOptions) => {
                setOptions(fetchedOptions);
                setLoading(false);
            })
            .catch((fetchError) => {
                setError(fetchError);
                setLoading(false);
            });
    }, [stockId, type]);

    return { options, loading, error };
};

export default useGetStockOptions;
