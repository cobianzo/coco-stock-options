import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useState } from 'react';

// types
import { WPAllOptionsData } from 'src/types/types';

const useGetStockOptions = (stockId: number, type?: 'put' | 'call') => {
	const [optionsData, setOptionsData] = useState<WPAllOptionsData>({});
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState<Error | null>(null);

	const fetchOptions = useCallback(() => {
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

	useEffect(() => {
		fetchOptions();
	}, [fetchOptions]);

	return { optionsData, loading, error, refetch: fetchOptions };
};

export default useGetStockOptions;
