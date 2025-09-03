import apiFetch from '@wordpress/api-fetch';
import React, { useState } from 'react';

function ReloadData({ stockPostTitle, refetch }: { stockPostTitle?: string; refetch: () => void }) {
	const [error, setError] = useState<string | null>(null);
	const [isLoading, setIsLoading] = useState(false);

	const handleReloadData = async function () {
		if (!stockPostTitle) return;

		setIsLoading(true);
		setError(null);

		// sync the post meta of the CPT stock for the current one
		apiFetch({
			path: `/coco/v1/sync-stock/${stockPostTitle}`,
			method: 'POST',
		})
			.then((fetchedOptions) => {
				refetch();
			})
			.catch(() => {
				setError('Failed to reload data. Please try again.');
				// Clear error after 3 seconds
				setTimeout(() => setError(null), 3000);
			})
			.finally(() => {
				setIsLoading(false);
			});
	};

	return (
		<>
			<button className={`reload-button ${isLoading ? 'loading' : ''}`} onClick={handleReloadData} title="Reload Data" disabled={isLoading}>
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="24"
					height="24"
					viewBox="0 0 24 24"
					fill="none"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				>
					<path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.3" />
				</svg>
			</button>
			{error && <div className="error-message">{error}</div>}
		</>
	);
}

export default ReloadData;
