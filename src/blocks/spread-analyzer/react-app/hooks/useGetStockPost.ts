import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const useGetStockPost = (postId: number) => {
    const [post, setPost] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!postId) {
            setLoading(false);
            setPost(null);
            return;
        }

        setLoading(true);
        apiFetch({ path: `/wp/v2/stock/${postId}` })
            .then((fetchedPost) => {
                setPost(fetchedPost);
                setLoading(false);
            })
            .catch((fetchError) => {
                setError(fetchError);
                setLoading(false);
            });
    }, [postId]);

    return { post, loading, error };
};

export default useGetStockPost;
