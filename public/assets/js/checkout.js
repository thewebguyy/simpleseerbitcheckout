document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('checkout-form');
    const payBtn = document.getElementById('pay-btn');
    const btnText = payBtn.querySelector('.btn-text');
    const btnLoader = payBtn.querySelector('.btn-loader');

    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Clear previous errors
        document.querySelectorAll('.error-msg').forEach(el => el.textContent = '');
        
        setLoading(true);

        try {
            const formData = new FormData(form);
            
            // 1. Initialize payment via our backend (JSON endpoint)
            const initResponse = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await initResponse.json();

            if (!initResponse.ok) {
                handleValidationErrors(result.errors || {});
                if (result.message && !result.errors) {
                    alert(result.message);
                }
                setLoading(false);
                return;
            }

            // 2. We have the token. Call SeerBit SDK.
            if (result.success && result.data.token) {
                invokeSeerBitSDK(result.data.token, result.data.order_reference);
            } else {
                alert('Payment initialization succeeded but no token was returned.');
                setLoading(false);
            }

        } catch (error) {
            console.error('Initialization error:', error);
            alert('A network error occurred. Please try again.');
            setLoading(false);
        }
    });

    function invokeSeerBitSDK(token, orderRef) {
        // We use the token returned from the server.
        // No public key or hardcoded amount in JS!
        
        let retryCount = 0;
        const maxRetries = 3;

        function attemptPayment() {
            SeerbitPay({
                "paylink": token // Using the redirectLink/paylink from SeerBit
            }, 
            function callback(response) {
                // Success Callback
                console.log("SeerBit Success:", response);
                // Redirect to our confirmation page
                window.location.href = `/checkout/confirmation?ref=${orderRef}`;
            }, 
            function close(close) {
                // Close Callback
                console.log("SeerBit Closed:", close);
                setLoading(false);
                // Optionally hit a cancel endpoint here
            });
        }

        // Only retry on network failure before modal opens, not on user close.
        // The modal itself handles SeerBit's internal retries.
        try {
            attemptPayment();
        } catch (err) {
            // V-15 Fix: Exponential backoff
            if (retryCount < maxRetries) {
                retryCount++;
                const backoff = Math.pow(2, retryCount) * 500; // 1s, 2s, 4s
                console.warn(`SDK Error. Retrying in ${backoff}ms...`);
                setTimeout(attemptPayment, backoff);
            } else {
                alert('Unable to load payment gateway. Please check your connection.');
                setLoading(false);
            }
        }
    }

    function setLoading(isLoading) {
        payBtn.disabled = isLoading;
        btnText.style.display = isLoading ? 'none' : 'inline';
        btnLoader.style.display = isLoading ? 'inline' : 'none';
    }

    function handleValidationErrors(errors) {
        for (const [field, msgs] of Object.entries(errors)) {
            const errorEl = document.getElementById(`err_${field}`);
            if (errorEl && msgs.length > 0) {
                errorEl.textContent = msgs[0];
            }
        }
    }
});
