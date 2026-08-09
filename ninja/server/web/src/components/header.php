<div class="text-center card">
    <div class="card-body">
        <div class=""><a href="/"><img class="logo" alt="Logo" src="/images/SCHOOLZ.png" style="max-width: 300px;"></a>
        </div>
        <div class="mt-3">
            <?php
            if ($isLogged) {
                echo '
                    <div>
                        <span style="color: #FFF">Xin chào, <span class="fw-bold text-warning">' . $user['username'] . ' - ' . number_format($user['balance']) . ' TCoin' . '</span></span>
                            <div class="text-warning">
                                <a class="fw-bold cursor-pointer text-warning" href="/user/profile">Tài khoản</a>
                                - <span class="fw-bold cursor-pointer me-1"><a class="fw-bold cursor-pointer text-warning" href="/logout">Đăng xuất</a></span>
                                <svg aria-hidden="true"
                                    focusable="false" data-prefix="fas" data-icon="arrow-right-to-bracket"
                                    class="svg-inline--fa fa-arrow-right-to-bracket " role="img" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 512 512" width="16" height="16">
                                    <path fill="currentColor"
                                        d="M352 96l64 0c17.7 0 32 14.3 32 32l0 256c0 17.7-14.3 32-32 32l-64 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l64 0c53 0 96-43 96-96l0-256c0-53-43-96-96-96l-64 0c-17.7 0-32 14.3-32 32s14.3 32 32 32zm-9.4 182.6c12.5-12.5 12.5-32.8 0-45.3l-128-128c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L242.7 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l210.7 0-73.4 73.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l128-128z">
                                    </path>
                                </svg>
                            </div>
                    </div>';
                if ($user['activated'] != 1) {
                    echo '<div class="mt-2">
                        <small class="text-danger fw-semibold mt-3">Tài khoản của bạn chưa được kích hoạt, click vào phía dưới để kích hoạt.</small>
                        <div class="mt-2">
                            <a data-bs-toggle="modal" data-bs-target="#modalActive" class="mb-3 px-2 py-1 fw-semibold text-secondary bg-danger bg-opacity-25 border border-danger border-opacity-75 rounded-2" style="cursor: pointer !important; color: #FFFFFF !important;">Kích hoạt tài khoản</a>
                        </div>
                    </div>';
                }
            } else {
                echo '
                    <a class="mt-3" data-bs-toggle="modal" data-bs-target="#modalLogin">
                        <span class="btn btn-success me-2 px-3 py-1">Đăng nhập</span>
                    </a>
                    <a class="mt-3" data-bs-toggle="modal" data-bs-target="#modalRegister">
                        <span class="btn btn-success px-3 py-1">Đăng Ký</span>
                    </a>
                ';
            }
            ?>
        </div>
        <div class="mt-3"><a class="btn btn-success px-3 py-1" href="/download">Tải Game
        <svg fill="#000000" height="800px" width="800px" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 29.978 29.978" xml:space="preserve" class="download-icon"><g><path d="M25.462,19.105v6.848H4.515v-6.848H0.489v8.861c0,1.111,0.9,2.012,2.016,2.012h24.967c1.115,0,2.016-0.9,2.016-2.012
		v-8.861H25.462z"></path><path d="M14.62,18.426l-5.764-6.965c0,0-0.877-0.828,0.074-0.828s3.248,0,3.248,0s0-0.557,0-1.416c0-2.449,0-6.906,0-8.723
		c0,0-0.129-0.494,0.615-0.494c0.75,0,4.035,0,4.572,0c0.536,0,0.524,0.416,0.524,0.416c0,1.762,0,6.373,0,8.742
		c0,0.768,0,1.266,0,1.266s1.842,0,2.998,0c1.154,0,0.285,0.867,0.285,0.867s-4.904,6.51-5.588,7.193
		C15.092,18.979,14.62,18.426,14.62,18.426z"></path><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></g></svg>
        </a></div>
    </div>
</div>