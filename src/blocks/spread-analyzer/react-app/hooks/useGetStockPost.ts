import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import type { WPStock } from '../../../../types/wordpress';

/**
 * Custom hook to fetch a stock post by its ID.
 *
 * @param {number} postId - The ID of the stock post to fetch.
 *
 * @returns {WPStock|null} post - The stock post data.
 * @returns {boolean} loading - The loading state.
 * @returns {Error|null} error - The error state.
 */
const useGetStockPost = (postId: number) => {
    const [post, setPost] = useState<WPStock|null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<Error|null>(null);

    useEffect(() => {
        if (!postId) {
            setLoading(false);
            setPost(null);
            return;
        }

        setLoading(true);
        apiFetch({ path: `/wp/v2/stock/${postId}` })
            .then((fetchedPost: unknown) => {
                setPost(fetchedPost as WPStock);
                setLoading(false);
            })
            .catch((fetchError: Error) => {
                setError(fetchError as Error);
                setLoading(false);
            });
    }, [postId]);

    return { post, loading, error };
};

export default useGetStockPost;
