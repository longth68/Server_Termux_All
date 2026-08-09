document.addEventListener('DOMContentLoaded', function() {
    function getRandomPosition(current) {
        let x, y, distance;
        do {
            x = Math.floor(-33 * Math.random());
            y = Math.floor(-33 * Math.random());
            distance = Math.sqrt(Math.pow(x - current.x, 2) + Math.pow(y - current.y, 2));
        } while (distance > 25 || distance < 20);
        return {x, y};
    }

    const background = document.querySelector('.background');

    let currentPosition = {x: 0, y: 0};

    function updatePosition() {
        currentPosition = getRandomPosition(currentPosition);
        background.style.transform = `translate(${currentPosition.x}%, ${currentPosition.y}%)`;
    }

    setInterval(updatePosition, 5000);

    setTimeout(updatePosition, 50);



    const helloContainer = document.getElementById('hello-container');
    const mainContainer = document.getElementById('container');
    let countdown = 3;
    let isButtonVisible = false;

    function startCountdown(setShowHello) {
        isButtonVisible = true;
        renderHello(setShowHello);

        const interval = setInterval(() => {
            countdown--;
            if (countdown < 0) {
                clearInterval(interval);
                setShowHello(false);
                renderHello(setShowHello);
            } else {
                renderHello(setShowHello);
            }
        }, 1000);
    }

    function renderHello(setShowHello) {
        helloContainer.innerHTML = `
        <div class="d-flex justify-content-center align-items-center min-vh-100 container">
        <div class="text-center p-3 w-100 main-border card" style="background: rgb(4, 58, 52);">
            <div class="card-body">
                <div class="Typewriter" data-testid="typewriter-wrapper"><span id="typed-text" class="fw-semibold fs-6"></span><span
                            class="Typewriter__cursor"></span></div>
                            ${isButtonVisible ? `<button class="btn btn-success mt-3" onclick="hideHello()">Trang chủ (${countdown})</button>` : ''}
            </div>
        </div>
    </div>`;
        if (!isButtonVisible) {
            const typed = new Typed('#typed-text', {
                strings: [
                    "Xin chào Nhẫn Giả!",
                    "Chào mừng bạn tới với thế giới của Ninja School"
                ],
                typeSpeed: 30,
                backSpeed: 30,
                loop: false,
                onComplete: function () {
                    startCountdown(setShowHello);
                }
            });
        } else {
            document.getElementById('typed-text').innerText = "Chào mừng bạn tới với thế giới của Ninja School";
        }
    }

    window.hideHello = function() {
        setShowHello(false);
    };

    function setShowHello(show) {
        if (show) {
            renderHello(setShowHello);
        } else {
            helloContainer.classList.add('d-none');
            mainContainer.classList.remove('d-none');
        }
    }

    function checkLastSeen() {
        const lastSeen = parseInt(localStorage.getItem("lastSeenHello") || "0", 10);
        if (!lastSeen || Date.now() - lastSeen > 432000000) {
            localStorage.setItem("lastSeenHello", Date.now().toString());
            setShowHello(true);
        } else {
            setShowHello(false);
        }
    }

    checkLastSeen();

});
