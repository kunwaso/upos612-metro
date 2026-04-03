<li class="grid-item">
    <div class="shop-box pb-25px">
        <div class="shop-image">
            <a href="{{ $card['url'] }}">
                <img src="{{ $card['image_url'] }}" alt="{{ $card['name'] }}" />
                @if(! empty($card['is_new']))
                    <span class="lable new">New</span>
                @endif
                <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div>
            </a>
            <div class="shop-hover d-flex justify-content-center">
                <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                <a href="{{ $card['url'] }}" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
            </div>
        </div>
        <div class="shop-footer text-center pt-20px">
            <a href="{{ $card['url'] }}" class="text-dark-gray fs-17 alt-font fw-600">{{ $card['name'] }}</a>
            <div class="fw-500 fs-15 lh-normal">{{ $card['price_label'] }}</div>
        </div>
    </div>
</li>
