        <!-- footer -->
        <footer>
            <div class="footer-image">
                <img class="effectFade fadeUp" src="{{ asset('modules/cms/assets/images/logo/logo-footer.png') }}" alt="">
            </div>
            <div class="container">
                <div class="footer-content">
                    <a href="{{ url('/') }}" class="footer-logo">
                        <img src="{{ asset('modules/cms/assets/images/logo/logo-2.svg') }}" alt="">
                    </a>
                    <div class="title h6 fw-semibold">Get connected <br> with Aigocy  on social</div>
                    <div class="text">Don’t miss our new updates!</div>
                    <div class="tf-social-1 justify-content-center">
                        <a href="https://x.com/" target="_blank" class="text-body-1 fw-semibold">
                            Twitter / X
                            <div class="social-item">
                                <i class="icon icon-twitter-x"></i>
                            </div>
                        </a>
                        <a href="https://www.facebook.com/" target="_blank" class="text-body-1 fw-semibold">
                            Facebook
                            <div class="social-item">
                                <i class="icon icon-facebook-f"></i>
                            </div>
                        </a>
                        <a href="https://www.instagram.com/" target="_blank" class="text-body-1 fw-semibold">
                            Instagram
                            <div class="social-item">
                                <i class="icon icon-instagram"></i>
                            </div>
                        </a>
                        <a href="https://www.linkedin.com/" target="_blank" class="text-body-1 fw-semibold">
                            Linkedin
                            <div class="social-item">
                                <i class="icon icon-linkedin-in"></i>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="footer-bottom">
                    <ul class="footer-links d-flex gap-24 align-items-center">
                        <li>
                            <a href="{{ url('shop/page/about') }}" class="fw-semibold link-underline link1">About</a>
                        </li>
                        <li>
                            <a href="{{ url('shop/page/services') }}" class="fw-semibold link-underline link1">Services</a>
                        </li>
                        <li>
                            <a href="{{ url('shop/page/works') }}" class="fw-semibold link-underline link1">Works</a>
                        </li>
                        <li>
                            <a href="{{ route('cms.contact.us') }}" class="fw-semibold link-underline link1">Contact</a>
                        </li>
                    </ul>
                    <p class="text-secondary coppy-rights text-center">© 2026 Aigocy . All Rights Reserved.</p>
                    <a href="#wrapper" class="action-go-top d-flex gap-8 align-items-center justify-content-end link1">
                        <span class="fw-semibold">Back to top</span>
                        <i class="icon icon-long-arrow-alt-up-solid fs-20"></i>
                    </a>    
                </div>
            </div>
        </footer>
        <!-- /footer -->