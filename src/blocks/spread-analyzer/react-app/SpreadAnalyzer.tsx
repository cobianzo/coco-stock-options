import React from 'react';
import useGetStockPost from './hooks/useGetStockPost';
import useGetStockOptions from './hooks/useGetStockOptions';
import { extractDateFromSymbol } from './helpers/sanitazors';

const SpreadAnalyzerApp = ({ side, stockId }: { side: 'PUT' | 'CALL', stockId: number }) => {

		const [spreadDates, setSpreadDates] = React.useState<string[]>([]);
    const { post, loading: postLoading, error: postError } = useGetStockPost(stockId);
    const { options, loading: optionsLoading, error: optionsError } = useGetStockOptions(stockId, side.toLowerCase() as 'put' | 'call');

		React.useEffect(() => {
			if (! options) return;

			setSpreadDates(Object.keys(options).map( date6digits => extractDateFromSymbol(date6digits).toLocaleDateString() ) );

		}, [options]);

    return (
        <div>
            <h3>{ side == 'PUT' ? 'Bear Put' : 'Bull Call' } Spread Analyzer for { post?.title?.rendered || 'Unknown' } </h3>
            {postLoading && <p>Loading post data...</p>}
            {postError && <p>Error fetching post: {postError.message}</p>}


            <h4>Stock Options Data:</h4>
            {optionsLoading && <p>Loading options data...</p>}
            {optionsError && <p>Error fetching options: {optionsError.message}</p>}
            {options && (
                <pre>
                    <code>{Object.keys(options).length} dates</code>
                    <br />
                    <code>{spreadDates.join(', ')}</code>
                </pre>
            )}
        </div>
    );
};

export default SpreadAnalyzerApp;
