import React from 'react';
import useGetStockPost from './hooks/useGetStockPost';
import useGetStockOptions from './hooks/useGetStockOptions';

const SpreadAnalyzerApp = ({ side, stockId }) => {
    const { post, loading: postLoading, error: postError } = useGetStockPost(stockId);
    const { options, loading: optionsLoading, error: optionsError } = useGetStockOptions(stockId, side.toLowerCase() as 'put' | 'call');

    return (
        <div>
            <h3>SpreadAnalyzer Frontend</h3>
            <p>Selected Side: <strong>{side}</strong></p>
            <p>Selected Stock ID: <strong>{stockId}</strong></p>

            <h4>Stock Post Data:</h4>
            {postLoading && <p>Loading post data...</p>}
            {postError && <p>Error fetching post: {postError.message}</p>}


            <h4>Stock Options Data:</h4>
            {optionsLoading && <p>Loading options data...</p>}
            {optionsError && <p>Error fetching options: {optionsError.message}</p>}
            {options && (
                <pre>
                    <code>{Object.keys(options).length} dates</code>
                </pre>
            )}
        </div>
    );
};

export default SpreadAnalyzerApp;
