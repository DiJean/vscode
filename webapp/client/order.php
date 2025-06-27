<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>??????? ??????</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/webapp/css/style.css">
    <style>
        .form-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 14px;
            border-radius: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-size: 1rem;
        }
        
        input::placeholder, textarea::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ff2e63 0%, #ff6b6b 100%);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="greeting">????? ??????</div>
        
        <div class="form-container">
            <form id="request-form">
                <div class="form-group">
                    <label for="fullName">??? ? ???????</label>
                    <input type="text" id="fullName" name="fullName" required placeholder="??????? ???? ?????? ???">
                </div>
                
                <div class="form-group">
                    <label for="phone">???????</label>
                    <input type="tel" id="phone" name="phone" required placeholder="+7 (XXX) XXX-XX-XX">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="example@mail.com">
                </div>
                
                <div class="form-group">
                    <label for="service">??????</label>
                    <select id="service" name="service" required>
                        <option value="" disabled selected>???????? ??????</option>
                        <option value="???? ?? ???????">???? ?? ???????</option>
                        <option value="????????? ?????????">????????? ?????????</option>
                        <option value="???????? ??????">???????? ??????</option>
                        <option value="??????????????? ???????">??????????????? ???????</option>
                        <option value="?????? ??????">?????? ??????</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="serviceDate">???????? ???? ??????</label>
                    <input type="date" id="serviceDate" name="serviceDate" required>
                </div>
                
                <div class="form-group">
                    <label for="city">?????</label>
                    <input type="text" id="city" name="city" required placeholder="????? ???????? ??????">
                </div>
                
                <div class="form-group">
                    <label for="cemetery">????????</label>
                    <input type="text" id="cemetery" name="cemetery" required placeholder="???????? ????????">
                </div>
                
                <div class="form-group">
                    <label for="sector">??????</label>
                    <input type="text" id="sector" name="sector" required placeholder="????? ???????">
                </div>
                
                <div class="form-group">
                    <label for="row">???</label>
                    <input type="text" id="row" name="row" required placeholder="????? ????">
                </div>
                
                <div class="form-group">
                    <label for="plot">???????</label>
                    <input type="text" id="plot" name="plot" required placeholder="????? ???????">
                </div>
                
                <div class="form-group">
                    <label for="comments">?????????????? ??????????</label>
                    <textarea id="comments" name="comments" rows="3" placeholder="?????? ????????? ??? ???????????"></textarea>
                </div>
                
                <button type="submit">????????? ??????</button>
            </form>
        </div>
    </div>

    <script src="/webapp/js/bitrix-integration.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('request-form');
            const tg = window.Telegram && Telegram.WebApp;
            
            // ???? ???????????? ??????????? ? Telegram
            if (tg && tg.initDataUnsafe && tg.initDataUnsafe.user) {
                const user = tg.initDataUnsafe.user;
                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                
                // ?????????????? ?????, ???? ????????
                if (firstName || lastName) {
                    document.getElementById('fullName').value = `${firstName} ${lastName}`.trim();
                }
            }
            
            // ????????? ??????? ???? ??? ??????????? ??? ??????
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('serviceDate').min = today;
            
            // ????????? ???????? ?????
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // ???????? ?????? ?????
                const formData = {
                    fullName: document.getElementById('fullName').value,
                    phone: document.getElementById('phone').value,
                    email: document.getElementById('email').value,
                    service: document.getElementById('service').value,
                    serviceDate: document.getElementById('serviceDate').value,
                    city: document.getElementById('city').value,
                    cemetery: document.getElementById('cemetery').value,
                    sector: document.getElementById('sector').value,
                    row: document.getElementById('row').value,
                    plot: document.getElementById('plot').value,
                    comments: document.getElementById('comments').value
                };
                
                try {
                    // ?????????? ?????? ? Bitrix24
                    const response = await createServiceRequest(formData);
                    const result = await response.json();
                    
                    if (result.result) {
                        // ????????? email ??? ???????????? ?????????????
                        localStorage.setItem('userEmail', formData.email);
                        
                        // ?????????? ???????????
                        if (tg && tg.showAlert) {
                            tg.showAlert('? ?????? ??????? ??????!');
                        } else {
                            alert('? ?????? ??????? ??????!');
                        }
                        
                        // ???????????? ? ?????? ????????
                        setTimeout(() => {
                            window.location.href = '/webapp/client/services.php';
                        }, 1500);
                    } else {
                        console.error('Bitrix24 error:', result);
                        const errorMsg = `? ??????: ${result.error_description || '??????????? ??????'}`;
                        
                        if (tg && tg.showAlert) {
                            tg.showAlert(errorMsg);
                        } else {
                            alert(errorMsg);
                        }
                    }
                } catch (error) {
                    console.error('Request failed:', error);
                    const errorMsg = '?? ?????? ???? ??? ???????. ?????????? ?????.';
                    
                    if (tg && tg.showAlert) {
                        tg.showAlert(errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                }
            });
        });
    </script>
</body>
</html>