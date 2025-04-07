document.addEventListener('DOMContentLoaded', () => {
    const flushRedisButton = document.querySelector('.flush-redis-button');
    const adminAjaxUrl = window.admin_ajax ? window.admin_ajax[0] : null;

    async function request(url, data) {
        try {
            const formData = new FormData();
            for (const key in data) {
                formData.append(key, data[key]);
            }

            const response = await fetch(url, {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}, body: ${errorText}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Request failed:', error);
            throw error; // Re-throw the error for the calling function to handle
        }
    }

    if (flushRedisButton && adminAjaxUrl) {
        flushRedisButton.addEventListener('click', async (event) => {
            document.body.classList.add('ajax-loading');

            try {
                const response = await request(
                    adminAjaxUrl,
                    {
                        action: 'send_request_flush_redis'
                    },
                );

                if (response.success) {
                    window.location.reload();
                } else {
                    document.body.classList.remove('ajax-loading');
                    console.error('Flush Redis Cache failed:', response);
                }
            } catch (error) {
                document.body.classList.remove('ajax-loading');
                console.error(error);
            }
        });
    }
});