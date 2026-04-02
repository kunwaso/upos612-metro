@extends('cms::frontend.layouts.app')
@section('title', 'Home')
@section('meta')
    <meta name="description" content="{{ $page->meta_description ?? '' }}">
@endsection
@section('content')

<!-- start section -->
<section class="p-0">
    <article class="content">
        <div id="decor-store-slider_wrapper" class="rev_slider_wrapper fullscreen-container">
            <!-- the ID here will be used in the JavaScript below to initialize the slider -->
            <div id="decor-store-slider" class="rev_slider fullscreenbanner" data-version="5.4.5" >
                <!-- begin slides list -->
                <ul>
                    <!-- minimum slide structure -->
                    <li  data-index="rs-73" data-transition="fade" data-slotamount="default" data-hideafterloop="0" data-hideslideonmobile="off"  data-easein="default" data-easeout="default" data-masterspeed="300"  data-thumb="http://works.themepunch.com/revolution_5_3/wp-content/"  data-rotate="0"  data-saveperformance="off"  data-title="Slide" data-param1="01" data-description="">
                        <!-- slide's main background image -->
                        <img src="{{ asset('modules/cms/assets/images/rev-trans-tile.png') }}" alt="dummy" class="rev-slidebg" data-bgcolor='#e5eff3'>
                        <!-- start shape layer -->
                        <div class="tp-caption tp-shape tp-shapewrapper tp-resizeme" 
                             id="slide-1-layer-01" 
                             data-x="['right','center','center','center']" data-hoffset="['280','0','0','0']" 
                             data-y="['middle','middle','middle','middle']" data-voffset="['60','0','0','0']" 
                             data-width="['800','600','600','600']"
                             data-height="['800','600','600','600']"
                             data-whitespace="nowrap"
                             data-type="shape" 
                             data-responsive_offset="on" 
                             data-frames='[{"delay":900,"speed":500,"frame":"0","from":"x:0px;y:50px;rX:0deg;rY:0deg;rZ:0deg;sX:0.5;sY:0.5;opacity:0;","to":"o:1;","ease":"Back.easeOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;","ease":"Power3.easeInOut"}]'
                             data-textAlign="['inherit','inherit','inherit','inherit']"
                             data-paddingtop="[0,0,0,0]"
                             data-paddingright="[0,0,0,0]"
                             data-paddingbottom="[0,0,0,0]"
                             data-paddingleft="[0,0,0,0]"
                             data-basealign="slide"
                             style="background:rgba(255,255,255,0.33);border-radius:50%;">
                        </div>
                        <!-- end shape layer -->
                        <!-- start background pattern image layer -->
                        <div class="tp-caption tp-resizeme right-image-layer"
                             data-frames='[{"delay":500,"speed":300,"frame":"0","from":"x:50px;opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"x:50px;opacity:0;","ease":"power3.inOut"}]'
                             data-visibility="['on','on','off','off']"
                             id="slide-1-layer-02"
                             data-x="['right','right','right','center']"
                             data-y="['middle','middle','middle','middle']"
                             data-hoffset="['0','0','0','0']"
                             data-voffset="['0','0','0','0']"
                             data-width="['580','450','auto','auto']"
                             data-height="['auto','auto','auto','auto']"
                             data-whitespace="['normal','normal','nowrap','normal']"
                             data-paddingtop="[0,0,0,0]"
                             data-paddingright="[0,0,0,0]"
                             data-paddingbottom="[0,0,0,0]"
                             data-paddingleft="[0,0,0,0]"
                             data-type="image"
                             data-basealign="slide"
                             data-responsive_offset="on"
                             data-responsive="on">
                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-slider-01-img-02.jpg') }}" alt="background-pattern" data-ww="100%" data-hh="auto">
                        </div>
                        <!-- end background pattern image layer -->
                        <!-- start mobile background pattern image layer -->
                        <div class="tp-caption tp-resizeme right-image-layer"
                             data-frames='[{"delay":500,"speed":300,"frame":"0","from":"y:50px;opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;","ease":"power3.inOut"}]'
                             id="slide-1-layer-03"
                             data-visibility="['off','off','on','on']"
                             data-x="['right','right','center','center']"
                             data-y="['middle','middle','bottom','bottom']"
                             data-hoffset="['0','0','0','0']"
                             data-voffset="['0','0','-150','-150']"
                             data-width="['0','0','1400','1200']"
                             data-height="['auto','auto','auto','auto']"
                             data-whitespace="['normal','normal','nowrap','normal']"
                             data-paddingtop="[0,0,0,0]"
                             data-paddingright="[0,0,0,0]"
                             data-paddingbottom="[0,0,0,0]"
                             data-paddingleft="[0,0,0,0]"
                             data-type="image"
                             data-basealign="slide"
                             data-responsive_offset="on"
                             data-responsive="on">
                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-slider-01-img-03.jpg') }}" alt="background-pattern" data-ww="100%" data-hh="auto">
                        </div>
                        <!-- end mobile background pattern image layer -->
                        <!-- start product image layer -->
                        <div class="tp-caption tp-resizeme product-image-layer"
                             data-frames='[{"delay":1000,"speed":800,"frame":"0","from":"x:50px;opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"x:50px;opacity:0;","ease":"power3.inOut"}]'
                             id="slide-1-layer-04"
                             data-visibility="['on','on','on','on']"
                             data-x="['right','right','center','center']"
                             data-y="['middle','middle','bottom','middle']"
                             data-hoffset="['-780','-450','30','15']"
                             data-voffset="['100','30','100','200']"
                             data-width="['1678','1000','700','500']"
                             data-height="['auto','auto','auto','auto']"
                             data-type="image"
                             data-basealign="slide"
                             data-responsive_offset="on"
                             data-responsive="on">
                            <img src="https://placehold.co/1678x740" alt="background-pattern" data-ww="100%" data-hh="auto">
                        </div>
                        <!-- end product image layer -->
                        <!-- start row zone -->
                        <div id="rrzm_638" class="rev_row_zone rev_row_zone_middle" style="z-index: 8;">
                            <!-- start row layer -->
                            <div class="tp-caption" 
                                 id="slide-1-layer-05" 
                                 data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                 data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                 data-width="auto"
                                 data-height="auto"
                                 data-whitespace="nowrap"
                                 data-basealign="grid"
                                 data-type="row" 
                                 data-columnbreak="2" 
                                 data-responsive_offset="on" 
                                 data-responsive="off"
                                 data-frames='[{"delay":0,"speed":300,"frame":"0","from":"opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"power3.inOut"}]'
                                 data-margintop="[0,0,0,0]"
                                 data-marginright="[0,0,0,0]"
                                 data-marginbottom="[0,0,0,0]"
                                 data-marginleft="[30,30,0,0]"
                                 data-textAlign="['inherit','inherit','inherit','inherit']"
                                 data-paddingtop="[0,0,0,0]"
                                 data-paddingright="[0,0,0,0]"
                                 data-paddingbottom="[0,0,0,0]"
                                 data-paddingleft="[0,0,0,0]"
                                 style="z-index: 8; white-space: nowrap; font-size: 20px; line-height: 22px; font-weight: 400; color: #ffffff; letter-spacing: 0px;">
                                <!-- start column layer -->
                                <div class="tp-caption left-column" 
                                     id="slide-1-layer-06"
                                     data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                     data-y="['middle','middle','top','middle']" data-voffset="['0','0','0','0']" 
                                     data-width="auto"
                                     data-height="auto"
                                     data-whitespace="nowrap"
                                     data-basealign="grid"
                                     data-type="column" 
                                     data-responsive_offset="on" 
                                     data-responsive="off"
                                     data-frames='[{"delay":0,"speed":300,"frame":"0","from":"opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"power3.inOut"}]'
                                     data-columnwidth="50%"
                                     data-verticalalign="top"
                                     data-margintop="[0,0,0,0]"
                                     data-marginright="[0,0,0,0]"
                                     data-marginbottom="[0,0,0,0]"
                                     data-marginleft="[0,0,0,0]"
                                     data-textAlign="['left','left','center','center']"
                                     data-paddingtop="[0,0,0,0]"
                                     data-paddingright="[0,0,0,0]"
                                     data-paddingbottom="[0,0,200,0]"
                                     data-paddingleft="[0,0,0,0]"
                                     style="z-index: 9; width: 100%;">
                                    <!-- start title layer -->
                                    <div class="tp-caption alt-font" 
                                         id="slide-1-layer-07"
                                         data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                         data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                         data-fontsize="['120','100','100','90']"
                                         data-lineheight="['105','90','90','90']"
                                         data-fontweight="['700','700','700','800']"
                                         data-letterspacing="['-3','-2','-2','-2']"
                                         data-color="['#16202c','#16202c','#16202c','#16202c']"
                                         data-width="auto"
                                         data-height="auto"
                                         data-whitespace="normal"
                                         data-basealign="grid"
                                         data-type="text" 
                                         data-responsive_offset="off" 
                                         data-responsive="on"
                                         data-frames='[{"delay":"1200","speed":1000,"frame":"0","from":"y:50px;opacity:0;fb:10px;","to":"o:1;fb:0;","ease":"Power3.easeInOut"},{"delay":"wait","speed":600,"frame":"999","to":"auto:auto;fb:0;","ease":"Power3.easeInOut"}]'
                                         data-margintop="[0,0,0,0]"
                                         data-marginright="[0,0,0,0]"
                                         data-marginbottom="[0,0,0,0]"
                                         data-marginleft="[0,0,0,0]"
                                         data-textAlign="['left','left','center','center']"
                                         data-paddingtop="[0,0,0,0]"
                                         data-paddingright="[0,0,200,0]"
                                         data-paddingbottom="[0,0,0,0]"
                                         data-paddingleft="[0,0,200,0]">
                                        Corby sofas
                                    </div>
                                    <!-- end title  layer -->
                                    <!-- start subtitle layer -->
                                    <div class="tp-caption alt-font" 
                                         id="slide-1-layer-08"
                                         data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                         data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                         data-fontsize="['22','22','20','22']"
                                         data-lineheight="['30','50','36','35']"
                                         data-fontweight="['400','400','400','400']"
                                         data-width="auto"
                                         data-height="auto"
                                         data-whitespace="normal"
                                         data-basealign="grid"
                                         data-type="text" 
                                         data-responsive_offset="off" 
                                         data-responsive="on"
                                         data-frames='[{"delay":"1500","speed":1000,"frame":"0","from":"y:50px;opacity:0;fb:10px;","to":"o:1;fb:0;","ease":"Power3.easeInOut"},{"delay":"wait","speed":600,"frame":"999","to":"auto:auto;fb:0;","ease":"Power3.easeInOut"}]'
                                         data-margintop="[30,20,20,20]"
                                         data-marginright="[0,0,0,0]"
                                         data-marginbottom="[0,0,0,0]"
                                         data-marginleft="[0,0,0,0]"
                                         data-textAlign="['left','left','center','center']"
                                         data-paddingtop="[0,0,0,0]"
                                         data-paddingright="[0,0,0,0]"
                                         data-paddingbottom="[0,0,0,0]"
                                         data-paddingleft="[0,0,0,0]"
                                         style="color: rgba(22,32,44,0.8)">
                                        Price starting from <span class="text-decoration-line-bottom-medium fw-600 text-dark-gray">$199.00</span>
                                    </div>
                                    <!-- end subtitle layer -->
                                    <!-- start button layer -->
                                    <a href="demo-decor-store-shop.html" class="tp-caption alt-font text-uppercase btn btn-dark-gray btn-large btn-switch-text btn-round-edge btn-box-shadow d-inline-block shop-button btn-rounded" 
                                       id="slide-1-layer-09"
                                       data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                       data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                       data-fontsize="['14','13','14','14']"
                                       data-lineheight="['20','20','20','20']"
                                       data-fontweight="['600','600','600','600']"
                                       data-color="['#ffffff','#ffffff','#ffffff','#ffffff']"
                                       data-width="auto"
                                       data-height="auto"
                                       data-basealign="grid"
                                       data-type="text" 
                                       data-responsive_offset="off" 
                                       data-responsive="off"
                                       data-frames='[{"delay":"1700","speed":1000,"frame":"0","from":"y:50px;opacity:0;fb:10px;","to":"o:1;fb:0;","ease":"Power3.easeInOut"},{"delay":"wait","speed":600,"frame":"999","to":"auto:auto;fb:0;","ease":"Power3.easeInOut"}]'
                                       data-margintop="[40,30,30,25]"
                                       data-marginright="[0,0,0,0]"
                                       data-marginbottom="[0,0,0,0]"
                                       data-marginleft="[0,0,0,0]"
                                       data-textAlign="['left','left','center','center']"
                                       data-whitespace="['nowrap','normal','nowrap','normal']"
                                       data-paddingtop="[16,12,12,12]"
                                       data-paddingright="[29,24,22,20]"
                                       data-paddingbottom="[16,12,12,12]"
                                       data-paddingleft="[25,18,16,16]"
                                       style="color: rgba(22,32,44,0.8)">
                                        <i class="feather icon-feather-shopping-bag pe-10px icon-small"></i>Shop Now
                                    </a>
                                    <!-- end button layer -->
                                </div>
                                <!-- end column layer -->
                                <!-- start column layer -->
                                <div class="tp-caption right-column" 
                                     id="slide-1-layer-10"
                                     data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                     data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                     data-width="none"
                                     data-height="none"
                                     data-whitespace="nowrap"
                                     data-basealign="grid"
                                     data-type="column" 
                                     data-responsive_offset="on" 
                                     data-responsive="off"
                                     data-frames='[{"delay":"+0","speed":300,"frame":"0","from":"opacity:0;","to":"o:1;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;","ease":"Power3.easeInOut"}]'
                                     data-columnwidth="50%"
                                     data-verticalalign="top"
                                     data-margintop="[0,0,0,0]"
                                     data-marginright="[0,0,0,0]"
                                     data-marginbottom="[0,0,0,0]"
                                     data-marginleft="[0,0,0,0]"
                                     data-textAlign="['left','left','center','center']"
                                     data-paddingtop="[0,0,50,100]"
                                     data-paddingright="[0,0,0,0]"
                                     data-paddingbottom="[0,0,50,100]"
                                     data-paddingleft="[0,0,0,0]"
                                     style="z-index: 9; width: 100%;">
                                </div>
                                <!-- end column layer -->
                            </div>
                            <!-- end row layer -->
                        </div>
                        <!-- end row zone -->
                        <!-- start sub text layer -->
                        <div class="tp-caption alt-font d-flex align-items-center" 
                             id="slide-1-layer-11"
                             data-x="['right','right','left','left']" data-hoffset="['0','0','0','0']" 
                             data-y="['bottom','bottom','middle','middle']" data-voffset="['0','0','0','0']" 
                             data-fontsize="['14','14','36','30']"
                             data-lineheight="['20','20','40','35']"
                             data-fontweight="['600','400','400','400']"
                             data-color="['#16202c','#16202c','#16202c','#16202c']"
                             data-width="['auto','210','400','400']"
                             data-visibility="['on','on','off','off']"
                             data-height="auto"
                             data-basealign="slide"
                             data-type="text" 
                             data-responsive_offset="off" 
                             data-responsive="off"
                             data-actions='[{"event":"mouseenter","action":"startlayer","layer":"slide-1-layer-12","delay":""},{"event":"mouseleave","action":"stoplayer","layer":"slide-1-layer-12","delay":""}]'
                             data-frames='[{"delay":2000,"speed":300,"frame":"0","from":"opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"power3.inOut"}]'
                             data-margintop="[0,0,0,0]"
                             data-marginright="[60,30,0,0]"
                             data-marginbottom="[40,30,0,0]"
                             data-marginleft="[0,0,0,0]"
                             data-textAlign="['left','left','center','center']"
                             data-whitespace="['nowrap','normal','nowrap','normal']"
                             data-paddingtop="[0,0,0,0]"
                             data-paddingright="[0,0,0,0]"
                             data-paddingbottom="[0,0,0,0]"
                             data-paddingleft="[0,0,0,0]">
                            <span class="pe-20px">More information</span><a href="#" class="d-flex justify-content-center align-items-center icon-box w-45px h-45px rounded-circle border border-color-transparent-dark-very-light border-2"><i class="bi bi-info icon-extra-medium text-dark-gray"></i></a>
                        </div>
                        <!-- end sub text layer -->
                        <!-- start tooltip layer -->
                        <div class="tp-caption tooltip-arrow" 
                             id="slide-1-layer-12"
                             data-x="['right','right','center','center']" data-hoffset="['0','12','0','0']" 
                             data-y="['bottom','bottom','top','top']" data-voffset="['83','110','55','45']" 
                             data-width="310"
                             data-height="auto"
                             data-whitespace="normal"
                             data-fontsize="['14','14','36','30']"
                             data-lineheight="['26','26','40','35']"
                             data-fontweight="['300','300','400','400']"
                             data-type="text" 
                             data-basealign="slide" 
                             data-responsive_offset="on" 
                             data-responsive="off"
                             data-margintop="[0,0,0,0]"
                             data-marginright="[40,0,0,0]"
                             data-marginbottom="[20,0,0,0]"
                             data-marginleft="[0,0,0,0]"
                             data-frames='[{"from":"opacity:0;","speed":100,"to":"o:1;","delay":"bytrigger","split":"chars","splitdelay":0.02,"ease":"Power2.easeInOut"},{"delay":"bytrigger","speed":300,"to":"auto:auto;","ease":"nothing"}]'
                             data-textAlign="['left','left','center','center']"
                             data-paddingtop="[30,25,0,0]"
                             data-paddingright="[35,25,0,0]"
                             data-paddingbottom="[30,25,0,0]"
                             data-paddingleft="[35,25,0,0]"
                             data-lasttriggerstate="reset"
                             style="z-index: 36; background-color: rgba(22,32,44,0.85); color: rgba(255,255,255,0.8); border-radius: 6px;">The three-seater works not just as the primary seating option, but also as an elegant statement piece.</div>
                        <!-- end tooltip layer -->
                        <!-- start arrow group layer -->
                        <div class="navigation-arrow">
                            <!-- start next text layer -->
                            <div class="tp-caption rev-btn d-flex justify-content-center align-items-center text-derk-gray btn btn-large bg-white btn-slide-right pb-5px md-pb-0 text-uppercase" 
                                 id="slide-1-layer-13"
                                 data-x="['left','left','center','center']" data-hoffset="['0','0','-50','-80']" 
                                 data-y="['bottom','bottom','bottom','bottom']" data-voffset="['0','0','0','0']" 
                                 data-visibility="['on','on','on','on']"
                                 data-width="['150','150','110','110']"
                                 data-height="['115','115','80','80']"
                                 data-whitespace="nowrap"
                                 data-fontsize="['18','18','16','15']"
                                 data-fontweight="['700','700','700','700']"
                                 data-letterspacing="['0','0','0','0']"
                                 data-lineheight="['0','60','0','0']"
                                 data-type="button" 
                                 data-actions='[{"event":"click","action":"jumptoslide","slide":"next","delay":""}]'
                                 data-responsive_offset="off" 
                                 data-responsive="off"
                                 data-frames='[{"delay":"2200","speed":1000,"sfxcolor":"#ffffff","sfx_effect":"blockfromright","frame":"0","ease":"Power3.easeInOut"},{"delay":"wait","speed":500,"sfxcolor":"#ffffff","sfx_effect":"blocktoleft","frame":"999","to":"z:0;","ease":"Power2.easeInOut"},{"frame":"hover","speed":"500","ease":"Power1.easeInOut","to":"o:1;rX:0;rY:0;rZ:0;z:0;","style":"c:rgb(255,255,255);bc:rgba(255,255,255,0);"}]'
                                 data-textAlign="['center','center','center','center']"
                                 data-basealign="grid"
                                 data-paddingtop="[8,0,0,0]"
                                 data-paddingright="[0,0,0,0]"
                                 data-paddingbottom="[0,0,0,0]"
                                 data-paddingleft="[0,0,0,0]"
                                 data-margintop="[0,0,0,0]"
                                 data-marginright="[0,0,0,0]"
                                 data-marginbottom="[0,0,0,0]"
                                 data-marginleft="[0,0,0,0]"
                                 style="border: none;"
                                 >Next<span class="bg-dark-gray"></span>
                            </div>
                            <!-- end next text layer -->
                            <!-- start slider thumb layer -->
                            <div class="tp-caption rev-btn alt-font d-flex justify-content-start align-items-center text-derk-gray text-uppercase pb-5px next-btn"
                                 id="slide-1-layer-14"
                                 data-x="['left','left','center','center']" data-hoffset="['150','150','60','30']" 
                                 data-y="['bottom','bottom','bottom','bottom']" data-voffset="['0','0','0','0']" 
                                 data-visibility="['on','on','on','on']"
                                 data-width="['200','200','110','110']"
                                 data-height="['115','115','80','80']"
                                 data-whitespace="nowrap"
                                 data-fontsize="['18','18','22','15']"
                                 data-lineheight="['20','60','50','55']"
                                 data-fontweight="['700','700','700','700']"
                                 data-type="button" 
                                 data-actions='[{"event":"click","action":"jumptoslide","slide":"next","delay":""}]'
                                 data-responsive_offset="off" 
                                 data-responsive="off"
                                 data-frames='[{"delay":2600,"speed":1000,"sfxcolor":"#ffffff","sfx_effect":"blockfromright","frame":"0","from":"z:0;","to":"o:1;","ease":"Power4.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;","ease":"Power3.easeInOut"}]'
                                 data-textAlign="['center','center','center','center']"
                                 data-basealign="grid"
                                 style="border: none; background-image: url(https://placehold.co/200x115);">
                            </div>
                            <!-- end slider thumb layer -->
                        </div>
                        <!-- end arrow group layer -->
                    </li>
                    <!-- minimum slide structure -->
                    <li  data-index="rs-72" data-transition="fade" data-slotamount="default" data-hideafterloop="0" data-hideslideonmobile="off"  data-easein="default" data-easeout="default" data-masterspeed="300"  data-thumb="http://works.themepunch.com/revolution_5_3/wp-content/"  data-rotate="0"  data-saveperformance="off"  data-title="Slide" data-param1="01" data-description="">
                        <!-- slide's main background image -->
                        <img src="{{ asset('modules/cms/assets/images/rev-trans-tile.png') }}" alt="dummy" class="rev-slidebg" data-bgcolor='#f9f1eb'>
                        <!-- start shape layer -->
                        <div class="tp-caption tp-shape tp-shapewrapper tp-resizeme" 
                             id="slide-2-layer-01"
                             data-x="['right','center','center','center']" data-hoffset="['280','0','0','0']" 
                             data-y="['middle','middle','middle','middle']" data-voffset="['60','0','0','0']" 
                             data-width="['800','600','600','600']"
                             data-height="['800','600','600','600']"
                             data-whitespace="nowrap"
                             data-type="shape" 
                             data-responsive_offset="on" 
                             data-frames='[{"delay":900,"speed":500,"frame":"0","from":"x:0px;y:50px;rX:0deg;rY:0deg;rZ:0deg;sX:0.5;sY:0.5;opacity:0;","to":"o:1;","ease":"Back.easeOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;","ease":"Power3.easeInOut"}]'
                             data-textAlign="['inherit','inherit','inherit','inherit']"
                             data-paddingtop="[0,0,0,0]"
                             data-paddingright="[0,0,0,0]"
                             data-paddingbottom="[0,0,0,0]"
                             data-paddingleft="[0,0,0,0]"
                             data-basealign="slide"
                             style="background:rgba(255,255,255,0.33);border-radius:50%;">
                        </div>
                        <!-- end shape layer -->
                        <!-- start background pattern image layer -->
                        <div class="tp-caption tp-resizeme right-image-layer"
                             data-frames='[{"delay":500,"speed":300,"frame":"0","from":"x:50px;opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"x:50px;opacity:0;","ease":"power3.inOut"}]'
                             data-visibility="['on','on','off','off']"
                             id="slide-2-layer-02"
                             data-x="['right','right','right','center']"
                             data-y="['middle','middle','middle','middle']"
                             data-hoffset="['0','0','0','0']"
                             data-voffset="['0','0','0','0']"
                             data-width="['580','450','auto','auto']"
                             data-height="['auto','auto','auto','auto']"
                             data-whitespace="['normal','normal','nowrap','normal']"
                             data-paddingtop="[0,0,0,0]"
                             data-paddingright="[0,0,0,0]"
                             data-paddingbottom="[0,0,0,0]"
                             data-paddingleft="[0,0,0,0]"
                             data-type="image"
                             data-basealign="slide"
                             data-responsive_offset="on"
                             data-responsive="on">
                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-slider-02-img-05.jpg') }}" alt="background-pattern" data-ww="100%" data-hh="auto">
                        </div>
                        <!-- end background pattern image layer -->
                        <!-- start mobile background pattern image layer -->
                        <div class="tp-caption tp-resizeme right-image-layer"
                             data-frames='[{"delay":500,"speed":300,"frame":"0","from":"x:50px;opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"x:50px;opacity:0;","ease":"power3.inOut"}]'
                             data-visibility="['off','off','on','on']"
                             id="slide-2-layer-03"
                             data-x="['right','right','center','center']"
                             data-y="['middle','middle','bottom','bottom']"
                             data-hoffset="['0','0','0','0']"
                             data-voffset="['0','0','-150','-150']"
                             data-width="['0','0','1400','1200']"
                             data-height="['auto','auto','auto','auto']"
                             data-whitespace="['normal','normal','nowrap','normal']"
                             data-paddingtop="[0,0,0,0]"
                             data-paddingright="[0,0,0,0]"
                             data-paddingbottom="[0,0,0,0]"
                             data-paddingleft="[0,0,0,0]"
                             data-type="image"
                             data-basealign="slide"
                             data-responsive_offset="on"
                             data-responsive="on">
                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-slider-02-img-06.jpg') }}" alt="background-pattern" data-ww="100%" data-hh="auto">
                        </div>
                        <!-- end mobile background pattern image layer -->
                        <!-- start product image layer -->
                        <div class="tp-caption tp-resizeme product-image-layer"
                             data-frames='[{"delay":1000,"speed":800,"frame":"0","from":"x:50px;opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"x:50px;opacity:0;","ease":"power3.inOut"}]'
                             id="slide-2-layer-04"
                             data-visibility="['on','on','on','on']"
                             data-x="['right','right','center','center']"
                             data-y="['middle','middle','bottom','middle']"
                             data-hoffset="['-780','-450','140','100']"
                             data-voffset="['100','30','100','200']"
                             data-width="['1678','1000','700','500']"
                             data-height="['auto','auto','auto','auto']"
                             data-type="image"
                             data-basealign="slide"
                             data-responsive_offset="on"
                             data-responsive="on">
                            <img src="https://placehold.co/1678x740" alt="background-pattern" data-ww="100%" data-hh="auto">
                        </div>
                        <!-- end product image layer -->
                        <!-- start row zone -->
                        <div id="rrzm_639" class="rev_row_zone rev_row_zone_middle" style="z-index: 8;">
                            <!-- start row layer -->
                            <div class="tp-caption" 
                                 id="slide-2-layer-05" 
                                 data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                 data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                 data-width="auto"
                                 data-height="auto"
                                 data-whitespace="nowrap"
                                 data-basealign="grid"
                                 data-type="row" 
                                 data-columnbreak="2" 
                                 data-responsive_offset="on" 
                                 data-responsive="off"
                                 data-frames='[{"delay":0,"speed":300,"frame":"0","from":"opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"power3.inOut"}]'
                                 data-margintop="[0,0,0,0]"
                                 data-marginright="[0,0,0,0]"
                                 data-marginbottom="[0,0,0,0]"
                                 data-marginleft="[30,30,0,0]"
                                 data-textAlign="['inherit','inherit','inherit','inherit']"
                                 data-paddingtop="[0,0,0,0]"
                                 data-paddingright="[0,0,0,0]"
                                 data-paddingbottom="[0,0,0,0]"
                                 data-paddingleft="[0,0,0,0]"
                                 style="z-index: 8; white-space: nowrap; font-size: 20px; line-height: 22px; font-weight: 400; color: #ffffff; letter-spacing: 0px;">
                                <!-- start column layer -->
                                <div class="tp-caption left-column" 
                                     id="slide-2-layer-06"
                                     data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                     data-y="['middle','middle','top','middle']" data-voffset="['0','0','0','0']" 
                                     data-width="auto"
                                     data-height="auto"
                                     data-whitespace="nowrap"
                                     data-basealign="grid"
                                     data-type="column" 
                                     data-responsive_offset="on" 
                                     data-responsive="off"
                                     data-frames='[{"delay":0,"speed":300,"frame":"0","from":"opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"power3.inOut"}]'
                                     data-columnwidth="50%"
                                     data-verticalalign="top"
                                     data-margintop="[0,0,0,0]"
                                     data-marginright="[0,0,0,0]"
                                     data-marginbottom="[0,0,0,0]"
                                     data-marginleft="[0,0,0,0]"
                                     data-textAlign="['left','left','center','center']"
                                     data-paddingtop="[0,0,0,0]"
                                     data-paddingright="[0,0,0,0]"
                                     data-paddingbottom="[0,0,200,0]"
                                     data-paddingleft="[0,0,0,0]"
                                     style="z-index: 9; width: 100%;">
                                    <!-- start title layer -->
                                    <div class="tp-caption alt-font" 
                                         id="slide-2-layer-07"
                                         data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                         data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                         data-fontsize="['120','100','100','90']"
                                         data-lineheight="['105','90','90','90']"
                                         data-fontweight="['700','700','700','800']"
                                         data-letterspacing="['-3','-2','-2','-2']"
                                         data-color="['#16202c','#16202c','#16202c','#16202c']"
                                         data-width="auto"
                                         data-height="auto"
                                         data-whitespace="normal"
                                         data-basealign="grid"
                                         data-type="text" 
                                         data-responsive_offset="off" 
                                         data-responsive="on"
                                         data-frames='[{"delay":"1200","speed":1000,"frame":"0","from":"y:50px;opacity:0;fb:10px;","to":"o:1;fb:0;","ease":"Power3.easeInOut"},{"delay":"wait","speed":600,"frame":"999","to":"auto:auto;fb:0;","ease":"Power3.easeInOut"}]'
                                         data-margintop="[0,0,0,0]"
                                         data-marginright="[0,0,0,0]"
                                         data-marginbottom="[0,0,0,0]"
                                         data-marginleft="[0,0,0,0]"
                                         data-textAlign="['left','left','center','center']"
                                         data-paddingtop="[0,0,0,0]"
                                         data-paddingright="[0,0,200,0]"
                                         data-paddingbottom="[0,0,0,0]"
                                         data-paddingleft="[0,0,200,0]">
                                        Verona sofas
                                    </div>
                                    <!-- end title  layer -->
                                    <!-- start subtitle layer -->
                                    <div class="tp-caption alt-font" 
                                         id="slide-2-layer-08"
                                         data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                         data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                         data-fontsize="['22','22','20','22']"
                                         data-lineheight="['30','50','36','35']"
                                         data-fontweight="['400','400','400','400']"

                                         data-width="auto"
                                         data-height="auto"
                                         data-whitespace="normal"
                                         data-basealign="grid"
                                         data-type="text" 
                                         data-responsive_offset="off" 
                                         data-responsive="on"
                                         data-frames='[{"delay":"1500","speed":1000,"frame":"0","from":"y:50px;opacity:0;fb:10px;","to":"o:1;fb:0;","ease":"Power3.easeInOut"},{"delay":"wait","speed":600,"frame":"999","to":"auto:auto;fb:0;","ease":"Power3.easeInOut"}]'
                                         data-margintop="[30,20,20,20]"
                                         data-marginright="[0,0,0,0]"
                                         data-marginbottom="[0,0,0,0]"
                                         data-marginleft="[0,0,0,0]"
                                         data-textAlign="['left','left','center','center']"
                                         data-paddingtop="[0,0,0,0]"
                                         data-paddingright="[0,0,0,0]"
                                         data-paddingbottom="[0,0,0,0]"
                                         data-paddingleft="[0,0,0,0]"
                                         style="color: rgba(22,32,44,0.8)">
                                        Price starting from <span class="text-decoration-line-bottom-medium fw-600 text-dark-gray">$259.00</span>
                                    </div>
                                    <!-- end subtitle layer -->
                                    <!-- start button layer -->
                                    <a href="demo-decor-store-shop.html" class="tp-caption alt-font text-uppercase btn btn-dark-gray btn-large btn-switch-text btn-round-edge btn-box-shadow d-inline-block shop-button btn-rounded" 
                                       id="slide-2-layer-09"
                                       data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                       data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                       data-fontsize="['14','13','14','14']"
                                       data-lineheight="['20','20','20','20']"
                                       data-fontweight="['600','600','600','600']"
                                       data-color="['#ffffff','#ffffff','#ffffff','#ffffff']"
                                       data-width="auto"
                                       data-height="auto"
                                       data-basealign="grid"
                                       data-type="text" 
                                       data-responsive_offset="off" 
                                       data-responsive="off"
                                       data-frames='[{"delay":"1700","speed":1000,"frame":"0","from":"y:50px;opacity:0;fb:10px;","to":"o:1;fb:0;","ease":"Power3.easeInOut"},{"delay":"wait","speed":600,"frame":"999","to":"auto:auto;fb:0;","ease":"Power3.easeInOut"}]'
                                       data-margintop="[40,30,30,25]"
                                       data-marginright="[0,0,0,0]"
                                       data-marginbottom="[0,0,0,0]"
                                       data-marginleft="[0,0,0,0]"
                                       data-textAlign="['left','left','center','center']"
                                       data-whitespace="['nowrap','normal','nowrap','normal']"
                                       data-paddingtop="[16,12,12,12]"
                                       data-paddingright="[29,24,22,20]"
                                       data-paddingbottom="[16,12,12,12]"
                                       data-paddingleft="[25,18,16,16]"
                                       style="color: rgba(22,32,44,0.8)">
                                        <i class="feather icon-feather-shopping-bag pe-10px icon-small"></i>Shop Now
                                    </a>
                                    <!-- end button layer -->
                                </div>
                                <!-- end column layer -->
                                <!-- start column layer -->
                                <div class="tp-caption right-column" 
                                     id="slide-2-layer-10"
                                     data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                     data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                     data-width="none"
                                     data-height="none"
                                     data-whitespace="nowrap"
                                     data-basealign="grid"
                                     data-type="column" 
                                     data-responsive_offset="on" 
                                     data-responsive="off"
                                     data-frames='[{"delay":"+0","speed":300,"frame":"0","from":"opacity:0;","to":"o:1;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;","ease":"Power3.easeInOut"}]'
                                     data-columnwidth="50%"
                                     data-verticalalign="top"
                                     data-margintop="[0,0,0,0]"
                                     data-marginright="[0,0,0,0]"
                                     data-marginbottom="[0,0,0,0]"
                                     data-marginleft="[0,0,0,0]"
                                     data-textAlign="['left','left','center','center']"
                                     data-paddingtop="[0,0,50,100]"
                                     data-paddingright="[0,0,0,0]"
                                     data-paddingbottom="[0,0,50,100]"
                                     data-paddingleft="[0,0,0,0]"
                                     style="z-index: 9; width: 100%;">
                                </div>
                                <!-- end column layer -->
                            </div>
                            <!-- end row layer -->
                        </div>
                        <!-- end row zone -->
                        <!-- start sub text layer -->
                        <div class="tp-caption alt-font d-flex align-items-center" 
                             id="slide-2-layer-11"
                             data-x="['right','right','left','left']" data-hoffset="['0','0','0','0']" 
                             data-y="['bottom','bottom','middle','middle']" data-voffset="['0','0','0','0']" 
                             data-fontsize="['14','14','36','30']"
                             data-lineheight="['20','20','40','35']"
                             data-fontweight="['600','400','400','400']"
                             data-color="['#16202c','#16202c','#16202c','#16202c']"
                             data-width="['auto','210','400','400']"
                             data-visibility="['on','on','off','off']"
                             data-height="auto"
                             data-basealign="slide"
                             data-type="text" 
                             data-responsive_offset="off" 
                             data-responsive="off"
                             data-actions='[{"event":"mouseenter","action":"startlayer","layer":"slide-2-layer-12","delay":""},{"event":"mouseleave","action":"stoplayer","layer":"slide-2-layer-12","delay":""}]'
                             data-frames='[{"delay":2000,"speed":300,"frame":"0","from":"opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"power3.inOut"}]'
                             data-margintop="[0,0,0,0]"
                             data-marginright="[60,30,0,0]"
                             data-marginbottom="[40,30,0,0]"
                             data-marginleft="[0,0,0,0]"
                             data-textAlign="['left','left','center','center']"
                             data-whitespace="['nowrap','normal','nowrap','normal']"
                             data-paddingtop="[0,0,0,0]"
                             data-paddingright="[0,0,0,0]"
                             data-paddingbottom="[0,0,0,0]"
                             data-paddingleft="[0,0,0,0]">
                            <span class="pe-20px">More information</span><a href="#" class="d-flex justify-content-center align-items-center icon-box w-45px h-45px rounded-circle border border-color-transparent-dark-very-light border-2"><i class="bi bi-info icon-extra-medium text-dark-gray"></i></a>
                        </div>
                        <!-- end sub text layer -->
                        <!-- start tooltip layer -->
                        <div class="tp-caption tooltip-arrow" 
                             id="slide-2-layer-12" 
                             data-x="['right','right','center','center']" data-hoffset="['0','12','0','0']" 
                             data-y="['bottom','bottom','top','top']" data-voffset="['83','110','55','45']" 
                             data-width="310"
                             data-height="auto"
                             data-whitespace="normal"
                             data-fontsize="['14','14','36','30']"
                             data-lineheight="['26','26','40','35']"
                             data-fontweight="['300','300','400','400']"
                             data-type="text" 
                             data-basealign="slide" 
                             data-responsive_offset="on" 
                             data-responsive="off"
                             data-margintop="[0,0,0,0]"
                             data-marginright="[40,0,0,0]"
                             data-marginbottom="[20,0,0,0]"
                             data-marginleft="[0,0,0,0]"
                             data-frames='[{"from":"opacity:0;","speed":100,"to":"o:1;","delay":"bytrigger","split":"chars","splitdelay":0.02,"ease":"Power2.easeInOut"},{"delay":"bytrigger","speed":300,"to":"auto:auto;","ease":"nothing"}]'
                             data-textAlign="['left','left','center','center']"
                             data-paddingtop="[30,25,0,0]"
                             data-paddingright="[35,25,0,0]"
                             data-paddingbottom="[30,25,0,0]"
                             data-paddingleft="[35,25,0,0]"
                             data-lasttriggerstate="reset"
                             style="z-index: 36; background-color: rgba(22,32,44,0.85); color: rgba(255,255,255,0.8); border-radius: 6px;">The three-seater works not just as the primary seating option, but also as an elegant statement piece.</div>
                        <!-- end tooltip layer -->
                        <!-- start arrow group layer -->
                        <div class="navigation-arrow">
                            <!-- start next text layer -->
                            <div class="tp-caption rev-btn d-flex justify-content-center align-items-center text-derk-gray btn btn-large bg-white btn-slide-right pb-5px md-pb-0 text-uppercase" 
                                 id="slide-2-layer-13"
                                 data-x="['left','left','center','center']" data-hoffset="['0','0','-50','-80']" 
                                 data-y="['bottom','bottom','bottom','bottom']" data-voffset="['0','0','0','0']" 
                                 data-visibility="['on','on','on','on']"
                                 data-width="['150','150','110','110']"
                                 data-height="['115','115','80','80']"
                                 data-whitespace="nowrap"
                                 data-fontsize="['18','18','16','15']"
                                 data-fontweight="['700','700','700','700']"
                                 data-letterspacing="['0','0','0','0']"
                                 data-lineheight="['0','60','0','0']"
                                 data-type="button" 
                                 data-actions='[{"event":"click","action":"jumptoslide","slide":"next","delay":""}]'
                                 data-responsive_offset="off" 
                                 data-responsive="off"
                                 data-frames='[{"delay":"2200","speed":1000,"sfxcolor":"#ffffff","sfx_effect":"blockfromright","frame":"0","ease":"Power3.easeInOut"},{"delay":"wait","speed":500,"sfxcolor":"#ffffff","sfx_effect":"blocktoleft","frame":"999","to":"z:0;","ease":"Power2.easeInOut"},{"frame":"hover","speed":"500","ease":"Power1.easeInOut","to":"o:1;rX:0;rY:0;rZ:0;z:0;","style":"c:rgb(255,255,255);bc:rgba(255,255,255,0);"}]'
                                 data-textAlign="['center','center','center','center']"
                                 data-basealign="grid"
                                 data-paddingtop="[8,0,0,0]"
                                 data-paddingright="[0,0,0,0]"
                                 data-paddingbottom="[0,0,0,0]"
                                 data-paddingleft="[0,0,0,0]"
                                 data-margintop="[0,0,0,0]"
                                 data-marginright="[0,0,0,0]"
                                 data-marginbottom="[0,0,0,0]"
                                 data-marginleft="[0,0,0,0]"
                                 style="border: none;"
                                 >Next<span class="bg-dark-gray"></span>
                            </div>
                            <!-- end next text layer -->
                            <!-- start slider thumb layer -->
                            <div class="tp-caption rev-btn alt-font d-flex justify-content-start align-items-center text-derk-gray text-uppercase pb-5px next-btn"
                                 id="slide-2-layer-14"
                                 data-x="['left','left','center','center']" data-hoffset="['150','150','60','30']" 
                                 data-y="['bottom','bottom','bottom','bottom']" data-voffset="['0','0','0','0']" 
                                 data-visibility="['on','on','on','on']"
                                 data-width="['200','200','110','110']"
                                 data-height="['115','115','80','80']"
                                 data-whitespace="nowrap"
                                 data-fontsize="['18','18','22','15']"
                                 data-lineheight="['20','60','50','55']"
                                 data-fontweight="['700','700','700','700']"
                                 data-type="button" 
                                 data-actions='[{"event":"click","action":"jumptoslide","slide":"next","delay":""}]'
                                 data-responsive_offset="off" 
                                 data-responsive="off"
                                 data-frames='[{"delay":2600,"speed":1000,"sfxcolor":"#ffffff","sfx_effect":"blockfromright","frame":"0","from":"z:0;","to":"o:1;","ease":"Power4.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;","ease":"Power3.easeInOut"}]'
                                 data-textAlign="['center','center','center','center']"
                                 data-basealign="grid"
                                 style="border: none; background-image: url(https://placehold.co/200x115);">
                            </div>
                            <!-- end slider thumb layer -->
                        </div>
                        <!-- end arrow group layer -->
                    </li>
                    <!-- minimum slide structure -->
                    <li  data-index="rs-74" data-transition="fade" data-slotamount="default" data-hideafterloop="0" data-hideslideonmobile="off"  data-easein="default" data-easeout="default" data-masterspeed="300"  data-thumb="http://works.themepunch.com/revolution_5_3/wp-content/"  data-rotate="0"  data-saveperformance="off"  data-title="Slide" data-param1="01" data-description="">
                        <!-- slide's main background image -->
                        <img src="{{ asset('modules/cms/assets/images/rev-trans-tile.png') }}" alt="dummy" class="rev-slidebg" data-bgcolor='#e9fafb'>
                        <!-- start shape layer -->
                        <div class="tp-caption tp-shape tp-shapewrapper tp-resizeme" 
                             id="slide-3-layer-01"
                             data-x="['right','center','center','center']" data-hoffset="['280','0','0','0']" 
                             data-y="['middle','middle','middle','middle']" data-voffset="['60','0','0','0']" 
                             data-width="['800','600','600','600']"
                             data-height="['800','600','600','600']"
                             data-whitespace="nowrap"
                             data-type="shape" 
                             data-responsive_offset="on" 
                             data-frames='[{"delay":900,"speed":500,"frame":"0","from":"x:0px;y:50px;rX:0deg;rY:0deg;rZ:0deg;sX:0.5;sY:0.5;opacity:0;","to":"o:1;","ease":"Back.easeOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;","ease":"Power3.easeInOut"}]'
                             data-textAlign="['inherit','inherit','inherit','inherit']"
                             data-paddingtop="[0,0,0,0]"
                             data-paddingright="[0,0,0,0]"
                             data-paddingbottom="[0,0,0,0]"
                             data-paddingleft="[0,0,0,0]"
                             data-basealign="slide"
                             style="background:rgba(255,255,255,0.33);border-radius:50%;">
                        </div>
                        <!-- end shape layer -->
                        <!-- start background pattern image layer -->
                        <div class="tp-caption tp-resizeme right-image-layer"
                             data-frames='[{"delay":500,"speed":300,"frame":"0","from":"y:50px;opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;","ease":"power3.inOut"}]'
                             data-visibility="['on','on','off','off']"
                             id="slide-3-layer-02"
                             data-x="['right','right','right','center']"
                             data-y="['middle','middle','middle','middle']"
                             data-hoffset="['0','0','0','0']"
                             data-voffset="['0','0','0','0']"
                             data-width="['580','450','auto','auto']"
                             data-height="['auto','auto','auto','auto']"
                             data-whitespace="['normal','normal','nowrap','normal']"
                             data-paddingtop="[0,0,0,0]"
                             data-paddingright="[0,0,0,0]"
                             data-paddingbottom="[0,0,0,0]"
                             data-paddingleft="[0,0,0,0]"
                             data-type="image"
                             data-basealign="slide"
                             data-responsive_offset="on"
                             data-responsive="on">
                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-slider-03-img-08.jpg') }}" alt="background-pattern" data-ww="100%" data-hh="auto">
                        </div>
                        <!-- end background pattern image layer -->
                        <!-- start mobile background pattern image layer -->
                        <div class="tp-caption tp-resizeme right-image-layer"
                             data-frames='[{"delay":500,"speed":300,"frame":"0","from":"y:50px;opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;","ease":"power3.inOut"}]'
                             data-visibility="['off','off','on','on']"
                             id="slide-3-layer-03"
                             data-x="['right','right','center','center']"
                             data-y="['middle','middle','bottom','bottom']"
                             data-hoffset="['0','0','0','0']"
                             data-voffset="['0','0','-150','-150']"
                             data-width="['0','0','1400','1200']"
                             data-height="['auto','auto','auto','auto']"
                             data-whitespace="['normal','normal','nowrap','normal']"
                             data-paddingtop="[0,0,0,0]"
                             data-paddingright="[0,0,0,0]"
                             data-paddingbottom="[0,0,0,0]"
                             data-paddingleft="[0,0,0,0]"
                             data-type="image"
                             data-basealign="slide"
                             data-responsive_offset="on"
                             data-responsive="on">
                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-slider-03-img-09.jpg') }}" alt="background-pattern" data-ww="100%" data-hh="auto">
                        </div>
                        <!-- end mobile background pattern image layer -->
                        <!-- start product image layer -->
                        <div class="tp-caption tp-resizeme product-image-layer"
                             data-frames='[{"delay":1000,"speed":800,"frame":"0","from":"x:50px;opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"x:50px;opacity:0;","ease":"power3.inOut"}]'
                             id="slide-3-layer-04"
                             data-visibility="['on','on','on','on']"
                             data-x="['right','right','center','center']"
                             data-y="['middle','middle','bottom','middle']"
                             data-hoffset="['-780','-450','160','120']"
                             data-voffset="['100','30','100','200']"
                             data-width="['1678','1000','700','500']"
                             data-height="['auto','auto','auto','auto']"
                             data-type="image"
                             data-basealign="slide"
                             data-responsive_offset="on"
                             data-responsive="on">
                            <img src="https://placehold.co/1678x740" alt="background-pattern" data-ww="100%" data-hh="auto">
                        </div>
                        <!-- end product image layer -->
                        <!-- start row zone -->
                        <div id="rrzm_640" class="rev_row_zone rev_row_zone_middle" style="z-index: 8;">
                            <!-- start row layer -->
                            <div class="tp-caption" 
                                 id="slide-3-layer-05" 
                                 data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                 data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                 data-width="auto"
                                 data-height="auto"
                                 data-whitespace="nowrap"
                                 data-basealign="grid"
                                 data-type="row" 
                                 data-columnbreak="2" 
                                 data-responsive_offset="on" 
                                 data-responsive="off"
                                 data-frames='[{"delay":0,"speed":300,"frame":"0","from":"opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"power3.inOut"}]'
                                 data-margintop="[0,0,0,0]"
                                 data-marginright="[0,0,0,0]"
                                 data-marginbottom="[0,0,0,0]"
                                 data-marginleft="[30,30,0,0]"
                                 data-textAlign="['inherit','inherit','inherit','inherit']"
                                 data-paddingtop="[0,0,0,0]"
                                 data-paddingright="[0,0,0,0]"
                                 data-paddingbottom="[0,0,0,0]"
                                 data-paddingleft="[0,0,0,0]"
                                 style="z-index: 8; white-space: nowrap; font-size: 20px; line-height: 22px; font-weight: 400; color: #ffffff; letter-spacing: 0px;">
                                <!-- start column layer -->
                                <div class="tp-caption left-column" 
                                     id="slide-3-layer-06"
                                     data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                     data-y="['middle','middle','top','middle']" data-voffset="['0','0','0','0']" 
                                     data-width="auto"
                                     data-height="auto"
                                     data-whitespace="nowrap"
                                     data-basealign="grid"
                                     data-type="column" 
                                     data-responsive_offset="on" 
                                     data-responsive="off"
                                     data-frames='[{"delay":0,"speed":300,"frame":"0","from":"opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"power3.inOut"}]'
                                     data-columnwidth="50%"
                                     data-verticalalign="top"
                                     data-margintop="[0,0,0,0]"
                                     data-marginright="[0,0,0,0]"
                                     data-marginbottom="[0,0,0,0]"
                                     data-marginleft="[0,0,0,0]"
                                     data-textAlign="['left','left','center','center']"
                                     data-paddingtop="[0,0,0,0]"
                                     data-paddingright="[0,0,0,0]"
                                     data-paddingbottom="[0,0,200,0]"
                                     data-paddingleft="[0,0,0,0]"
                                     style="z-index: 9; width: 100%;">
                                    <!-- start title layer -->
                                    <div class="tp-caption alt-font" 
                                         id="slide-3-layer-07"
                                         data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                         data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                         data-fontsize="['120','100','100','90']"
                                         data-lineheight="['105','90','90','90']"
                                         data-fontweight="['700','700','700','800']"
                                         data-letterspacing="['-3','-2','-2','-2']"
                                         data-color="['#16202c','#16202c','#16202c','#16202c']"
                                         data-width="auto"
                                         data-height="auto"
                                         data-whitespace="normal"
                                         data-basealign="grid"
                                         data-type="text" 
                                         data-responsive_offset="off" 
                                         data-responsive="on"
                                         data-frames='[{"delay":"1200","speed":1000,"frame":"0","from":"y:50px;opacity:0;fb:10px;","to":"o:1;fb:0;","ease":"Power3.easeInOut"},{"delay":"wait","speed":600,"frame":"999","to":"auto:auto;fb:0;","ease":"Power3.easeInOut"}]'
                                         data-margintop="[0,0,0,0]"
                                         data-marginright="[0,0,0,0]"
                                         data-marginbottom="[0,0,0,0]"
                                         data-marginleft="[0,0,0,0]"
                                         data-textAlign="['left','left','center','center']"
                                         data-paddingtop="[0,0,0,0]"
                                         data-paddingright="[0,0,200,0]"
                                         data-paddingbottom="[0,0,0,0]"
                                         data-paddingleft="[0,0,200,0]">
                                        Lewis sofas
                                    </div>
                                    <!-- end title  layer -->
                                    <!-- start subtitle layer -->
                                    <div class="tp-caption alt-font" 
                                         id="slide-3-layer-08"
                                         data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                         data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                         data-fontsize="['22','22','20','22']"
                                         data-lineheight="['30','50','36','35']"
                                         data-fontweight="['400','400','400','400']"

                                         data-width="auto"
                                         data-height="auto"
                                         data-whitespace="normal"
                                         data-basealign="grid"
                                         data-type="text" 
                                         data-responsive_offset="off" 
                                         data-responsive="on"
                                         data-frames='[{"delay":"1500","speed":1000,"frame":"0","from":"y:50px;opacity:0;fb:10px;","to":"o:1;fb:0;","ease":"Power3.easeInOut"},{"delay":"wait","speed":600,"frame":"999","to":"auto:auto;fb:0;","ease":"Power3.easeInOut"}]'
                                         data-margintop="[30,20,20,20]"
                                         data-marginright="[0,0,0,0]"
                                         data-marginbottom="[0,0,0,0]"
                                         data-marginleft="[0,0,0,0]"
                                         data-textAlign="['left','left','center','center']"
                                         data-paddingtop="[0,0,0,0]"
                                         data-paddingright="[0,0,0,0]"
                                         data-paddingbottom="[0,0,0,0]"
                                         data-paddingleft="[0,0,0,0]"
                                         style="color: rgba(22,32,44,0.8)">
                                        Price starting from <span class="text-decoration-line-bottom-medium fw-600 text-dark-gray">$259.00</span>
                                    </div>
                                    <!-- end subtitle layer -->
                                    <!-- start button layer -->
                                    <a href="demo-decor-store-shop.html" class="tp-caption alt-font text-uppercase btn btn-dark-gray btn-large btn-switch-text btn-round-edge btn-box-shadow d-inline-block shop-button btn-rounded" 
                                       id="slide-3-layer-09"
                                       data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                       data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                       data-fontsize="['14','13','14','14']"
                                       data-lineheight="['20','20','20','20']"
                                       data-fontweight="['600','600','600','600']"
                                       data-color="['#ffffff','#ffffff','#ffffff','#ffffff']"
                                       data-width="auto"
                                       data-height="auto"
                                       data-basealign="grid"
                                       data-type="text" 
                                       data-responsive_offset="off" 
                                       data-responsive="off"
                                       data-frames='[{"delay":"1700","speed":1000,"frame":"0","from":"y:50px;opacity:0;fb:10px;","to":"o:1;fb:0;","ease":"Power3.easeInOut"},{"delay":"wait","speed":600,"frame":"999","to":"auto:auto;fb:0;","ease":"Power3.easeInOut"}]'
                                       data-margintop="[40,30,30,25]"
                                       data-marginright="[0,0,0,0]"
                                       data-marginbottom="[0,0,0,0]"
                                       data-marginleft="[0,0,0,0]"
                                       data-textAlign="['left','left','center','center']"
                                       data-whitespace="['nowrap','normal','nowrap','normal']"
                                       data-paddingtop="[16,12,12,12]"
                                       data-paddingright="[29,24,22,20]"
                                       data-paddingbottom="[16,12,12,12]"
                                       data-paddingleft="[25,18,16,16]"
                                       style="color: rgba(22,32,44,0.8)">
                                        <i class="feather icon-feather-shopping-bag pe-10px icon-small"></i>Shop Now
                                    </a>
                                    <!-- end button layer -->
                                </div>
                                <!-- end column layer -->
                                <!-- start column layer -->
                                <div class="tp-caption right-column" 
                                     id="slide-3-layer-10"
                                     data-x="['left','left','left','left']" data-hoffset="['0','0','0','0']" 
                                     data-y="['middle','middle','middle','middle']" data-voffset="['0','0','0','0']" 
                                     data-width="none"
                                     data-height="none"
                                     data-whitespace="nowrap"
                                     data-basealign="grid"
                                     data-type="column" 
                                     data-responsive_offset="on" 
                                     data-responsive="off"
                                     data-frames='[{"delay":"+0","speed":300,"frame":"0","from":"opacity:0;","to":"o:1;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;","ease":"Power3.easeInOut"}]'
                                     data-columnwidth="50%"
                                     data-verticalalign="top"
                                     data-margintop="[0,0,0,0]"
                                     data-marginright="[0,0,0,0]"
                                     data-marginbottom="[0,0,0,0]"
                                     data-marginleft="[0,0,0,0]"
                                     data-textAlign="['left','left','center','center']"
                                     data-paddingtop="[0,0,50,100]"
                                     data-paddingright="[0,0,0,0]"
                                     data-paddingbottom="[0,0,50,100]"
                                     data-paddingleft="[0,0,0,0]"
                                     style="z-index: 9; width: 100%;">
                                </div>
                                <!-- end column layer -->
                            </div>
                            <!-- end row layer -->
                        </div>
                        <!-- end row zone -->
                        <!-- start sub text layer -->
                        <div class="tp-caption alt-font d-flex align-items-center" 
                             id="slide-3-layer-11"
                             data-x="['right','right','left','left']" data-hoffset="['0','0','0','0']" 
                             data-y="['bottom','bottom','middle','middle']" data-voffset="['0','0','0','0']" 
                             data-fontsize="['14','14','36','30']"
                             data-lineheight="['20','20','40','35']"
                             data-fontweight="['600','400','400','400']"
                             data-color="['#16202c','#16202c','#16202c','#16202c']"
                             data-width="['auto','210','400','400']"
                             data-visibility="['on','on','off','off']"
                             data-height="auto"
                             data-basealign="slide"
                             data-type="text" 
                             data-responsive_offset="off" 
                             data-responsive="off"
                             data-actions='[{"event":"mouseenter","action":"startlayer","layer":"slide-3-layer-12","delay":""},{"event":"mouseleave","action":"stoplayer","layer":"slide-3-layer-12","delay":""}]'
                             data-frames='[{"delay":2000,"speed":300,"frame":"0","from":"opacity:0;","to":"o:1;","ease":"power3.inOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"power3.inOut"}]'
                             data-margintop="[0,0,0,0]"
                             data-marginright="[60,30,0,0]"
                             data-marginbottom="[40,30,0,0]"
                             data-marginleft="[0,0,0,0]"
                             data-textAlign="['left','left','center','center']"
                             data-whitespace="['nowrap','normal','nowrap','normal']"
                             data-paddingtop="[0,0,0,0]"
                             data-paddingright="[0,0,0,0]"
                             data-paddingbottom="[0,0,0,0]"
                             data-paddingleft="[0,0,0,0]">
                            <span class="pe-20px">More information</span><a href="#" class="d-flex justify-content-center align-items-center icon-box w-45px h-45px rounded-circle border border-color-transparent-dark-very-light border-2"><i class="bi bi-info icon-extra-medium text-dark-gray"></i></a>
                        </div>
                        <!-- end sub text layer -->
                        <!-- start tooltip layer -->
                        <div class="tp-caption tooltip-arrow" 
                             id="slide-3-layer-12"
                             data-x="['right','right','center','center']" data-hoffset="['0','12','0','0']" 
                             data-y="['bottom','bottom','top','top']" data-voffset="['83','110','55','45']" 
                             data-width="310"
                             data-height="auto"
                             data-whitespace="normal"
                             data-fontsize="['14','14','36','30']"
                             data-lineheight="['26','26','40','35']"
                             data-fontweight="['300','300','400','400']"
                             data-type="text" 
                             data-basealign="slide" 
                             data-responsive_offset="on" 
                             data-responsive="off"
                             data-margintop="[0,0,0,0]"
                             data-marginright="[40,0,0,0]"
                             data-marginbottom="[20,0,0,0]"
                             data-marginleft="[0,0,0,0]"
                             data-frames='[{"from":"opacity:0;","speed":100,"to":"o:1;","delay":"bytrigger","split":"chars","splitdelay":0.02,"ease":"Power2.easeInOut"},{"delay":"bytrigger","speed":300,"to":"auto:auto;","ease":"nothing"}]'
                             data-textAlign="['left','left','center','center']"
                             data-paddingtop="[30,25,0,0]"
                             data-paddingright="[35,25,0,0]"
                             data-paddingbottom="[30,25,0,0]"
                             data-paddingleft="[35,25,0,0]"
                             data-lasttriggerstate="reset"
                             style="z-index: 36; background-color: rgba(22,32,44,0.85); color: rgba(255,255,255,0.8); border-radius: 6px;">The three-seater works not just as the primary seating option, but also as an elegant statement piece.</div>
                        <!-- end tooltip layer -->
                        <!-- start arrow group layer -->
                        <div class="navigation-arrow">
                            <!-- start next text layer -->
                            <div class="tp-caption rev-btn d-flex justify-content-center align-items-center text-derk-gray btn btn-large bg-white btn-slide-right pb-5px md-pb-0 text-uppercase" 
                                 id="slide-3-layer-13"
                                 data-x="['left','left','center','center']" data-hoffset="['0','0','-50','-80']" 
                                 data-y="['bottom','bottom','bottom','bottom']" data-voffset="['0','0','0','0']" 
                                 data-visibility="['on','on','on','on']"
                                 data-width="['150','150','110','110']"
                                 data-height="['115','115','80','80']"
                                 data-whitespace="nowrap"
                                 data-fontsize="['18','18','16','15']"
                                 data-fontweight="['700','700','700','700']"
                                 data-letterspacing="['0','0','0','0']"
                                 data-lineheight="['0','60','0','0']"
                                 data-type="button" 
                                 data-actions='[{"event":"click","action":"jumptoslide","slide":"next","delay":""}]'
                                 data-responsive_offset="off" 
                                 data-responsive="off"
                                 data-frames='[{"delay":"2200","speed":1000,"sfxcolor":"#ffffff","sfx_effect":"blockfromright","frame":"0","ease":"Power3.easeInOut"},{"delay":"wait","speed":500,"sfxcolor":"#ffffff","sfx_effect":"blocktoleft","frame":"999","to":"z:0;","ease":"Power2.easeInOut"},{"frame":"hover","speed":"500","ease":"Power1.easeInOut","to":"o:1;rX:0;rY:0;rZ:0;z:0;","style":"c:rgb(255,255,255);bc:rgba(255,255,255,0);"}]'
                                 data-textAlign="['center','center','center','center']"
                                 data-basealign="grid"
                                 data-paddingtop="[8,0,0,0]"
                                 data-paddingright="[0,0,0,0]"
                                 data-paddingbottom="[0,0,0,0]"
                                 data-paddingleft="[0,0,0,0]"
                                 data-margintop="[0,0,0,0]"
                                 data-marginright="[0,0,0,0]"
                                 data-marginbottom="[0,0,0,0]"
                                 data-marginleft="[0,0,0,0]"
                                 style="border: none;"
                                 >Next<span class="bg-dark-gray"></span>
                            </div>
                            <!-- end next text layer -->
                            <!-- start slider thumb layer -->
                            <div class="tp-caption rev-btn alt-font d-flex justify-content-start align-items-center text-derk-gray text-uppercase pb-5px next-btn"
                                 id="slide-3-layer-14"
                                 data-x="['left','left','center','center']" data-hoffset="['150','150','60','30']" 
                                 data-y="['bottom','bottom','bottom','bottom']" data-voffset="['0','0','0','0']" 
                                 data-visibility="['on','on','on','on']"
                                 data-width="['200','200','110','110']"
                                 data-height="['115','115','80','80']"
                                 data-whitespace="nowrap"
                                 data-fontsize="['18','18','22','15']"
                                 data-lineheight="['20','60','50','55']"
                                 data-fontweight="['700','700','700','700']"
                                 data-type="button" 
                                 data-actions='[{"event":"click","action":"jumptoslide","slide":"next","delay":""}]'
                                 data-responsive_offset="off" 
                                 data-responsive="off"
                                 data-frames='[{"delay":2600,"speed":1000,"sfxcolor":"#ffffff","sfx_effect":"blockfromright","frame":"0","from":"z:0;","to":"o:1;","ease":"Power4.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;","ease":"Power3.easeInOut"}]'
                                 data-textAlign="['center','center','center','center']"
                                 data-basealign="grid"
                                 style="border: none; background-image: url(https://placehold.co/200x115);">
                            </div>
                            <!-- end slider thumb layer -->
                        </div>
                        <!-- end arrow group layer -->
                    </li>
                </ul>
            </div>
        </div>
    </article>
</section>
<!-- end section -->
<!-- start section -->
<section class="position-relative pt-0 overflow-hidden">
    <div class="container container position-relative z-index-9">
        <div class="row align-items-center mb-6 mt-7 xs-mb-30px xs-mt-35px">
            <div class="col-xl-2 col-lg-3 md-mb-40px" data-anime='{ "translateX": [50, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                <div class="feature-box feature-box-left-icon-middle mb-5px">
                    <div class="feature-box-icon me-5px">
                        <i class="bi bi-heart-fill text-red fs-13"></i> 
                    </div>
                    <div class="feature-box-content">
                        <span class="d-inline-block fs-16 fw-500 alt-font text-dark-gray">On demand</span>
                    </div>
                </div>
                <h6 class="mb-0 fw-700 alt-font text-dark-gray">Featured categories</h6>
            </div>
            <div class="col-xl-10 col-lg-9">
                <div class="row row-cols-2 row-cols-md-6 row-cols-sm-3 justify-content-center align-items-center" data-anime='{ "el": "childs", "translateX": [50, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                    <div class="col categories-style-01 text-center sm-mb-30px">
                        <div class="categories-box">
                            <div class="icon-box position-relative mb-10px">
                                <a href="demo-decor-store-shop.html"><img src="https://placehold.co/65x65" alt=""/></a>
                                <div class="count-circle d-flex align-items-center justify-content-center w-35px h-35px bg-base-color text-white rounded-circle alt-font fw-600 fs-12">02</div>
                            </div>
                            <a href="demo-decor-store-shop.html" class="alt-font fw-600 fs-17 text-dark-gray text-dark-gray-hover">Lamp</a>
                        </div>
                    </div>
                    <div class="col categories-style-01 text-center sm-mb-30px">
                        <div class="categories-box">
                            <div class="icon-box position-relative mb-10px">
                                <a href="demo-decor-store-shop.html"><img src="https://placehold.co/65x65" alt=""/></a>
                                <div class="count-circle d-flex align-items-center justify-content-center w-35px h-35px bg-base-color text-white rounded-circle alt-font fw-600 fs-12">03</div>
                            </div>
                            <a href="demo-decor-store-shop.html" class="alt-font fw-600 fs-17 text-dark-gray text-dark-gray-hover">Stool</a>
                        </div>
                    </div>
                    <div class="col categories-style-01 text-center sm-mb-30px">
                        <div class="categories-box">
                            <div class="icon-box position-relative mb-10px">
                                <a href="demo-decor-store-shop.html"><img src="https://placehold.co/65x65" alt=""/></a>
                                <div class="count-circle d-flex align-items-center justify-content-center w-35px h-35px bg-base-color text-white rounded-circle alt-font fw-600 fs-12">05</div>
                            </div>
                            <a href="demo-decor-store-shop.html" class="alt-font fw-600 fs-17 text-dark-gray text-dark-gray-hover">Chair</a>
                        </div>
                    </div>
                    <div class="col categories-style-01 text-center xs-mb-30px">
                        <div class="categories-box">
                            <div class="icon-box position-relative mb-10px">
                                <a href="demo-decor-store-shop.html"><img src="https://placehold.co/65x65" alt=""/></a>
                                <div class="count-circle d-flex align-items-center justify-content-center w-35px h-35px bg-base-color text-white rounded-circle alt-font fw-600 fs-12">03</div>
                            </div>
                            <a href="demo-decor-store-shop.html" class="alt-font fw-600 fs-17 text-dark-gray text-dark-gray-hover">Cabinet</a>
                        </div>
                    </div>
                    <div class="col categories-style-01 text-center">
                        <div class="categories-box">
                            <div class="icon-box position-relative mb-10px">
                                <a href="demo-decor-store-shop.html"><img src="https://placehold.co/65x65" alt=""/></a>
                                <div class="count-circle d-flex align-items-center justify-content-center w-35px h-35px bg-base-color text-white rounded-circle alt-font fw-600 fs-12">08</div>
                            </div>
                            <a href="demo-decor-store-shop.html" class="alt-font fw-600 fs-17 text-dark-gray text-dark-gray-hover">Light</a>
                        </div>
                    </div>
                    <div class="col categories-style-01 text-center">
                        <div class="categories-box">
                            <div class="icon-box position-relative mb-10px">
                                <a href="demo-decor-store-shop.html"><img src="https://placehold.co/65x65" alt=""/></a>
                                <div class="count-circle d-flex align-items-center justify-content-center w-35px h-35px bg-base-color text-white rounded-circle alt-font fw-600 fs-12">04</div>
                            </div>
                            <a href="demo-decor-store-shop.html" class="alt-font fw-600 fs-17 text-dark-gray text-dark-gray-hover">Sofa</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-0">
            <div class="col-12 filter-content">
                <ul class="shop-wrapper shop-grid grid-loading grid grid-2col xxl-grid-2col xl-grid-2col lg-grid-2col md-grid-1col sm-grid-1col xs-grid-1col gutter-large text-center" data-anime='{ "el": "childs", "translate": [50, 0], "opacity": [0,1], "duration": 600, "delay":150, "staggervalue": 150, "easing": "easeOutQuad" }'>
                    <li class="grid-sizer"></li>
                    <!-- start shop item -->
                    <li class="grid-item"> 
                        <div class="shop-box position-relative overflow-hidden">
                            <div class="shop-image">
                                <img src="https://placehold.co/580x540" alt="" />
                            </div>
                            <div class="position-absolute top-0 left-0 w-100 h-100 d-flex align-items-end justify-content-start text-start">
                                <div class="p-14 sm-p-10 w-80 lg-w-90 md-w-65 xs-w-300px">
                                    <h3 class="alt-font text-dark-gray ls-minus-1px mb-30px xs-mb-25px">Wooden <span class="fw-700">classic table</span></h3>
                                    <a href="demo-decor-store-shop.html" class="btn btn-dark-gray btn-large btn-switch-text btn-round-edge btn-box-shadow">
                                        <span>
                                            <span class="btn-double-text" data-text="Explore category">Explore category</span>
                                        </span>
                                    </a>
                                </div>
                            </div> 
                        </div> 
                    </li>
                    <!-- end shop item -->
                    <!-- start shop item -->
                    <li class="grid-item"> 
                        <div class="shop-box position-relative overflow-hidden">
                            <div class="shop-image">
                                <img src="https://placehold.co/580x260" alt="" />
                            </div>
                            <div class="position-absolute top-0 left-0 w-100 h-100 d-flex align-items-center justify-content-start text-start">
                                <div class="p-11 xs-p-8 w-60 md-w-50 xs-w-230px">
                                    <h5 class="alt-font text-dark-gray ls-minus-1px mb-25px xs-mb-20px">Pottery <span class="fw-700">products</span></h5>
                                    <a href="demo-decor-store-shop.html" class="btn btn-dark-gray btn-small btn-switch-text btn-round-edge btn-box-shadow">
                                        <span>
                                            <span class="btn-double-text" data-text="Explore category">Explore category</span> 
                                        </span>
                                    </a>
                                </div>
                            </div> 
                        </div> 
                    </li>
                    <!-- end shop item -->
                    <!-- start shop item -->
                    <li class="grid-item"> 
                        <div class="shop-box position-relative overflow-hidden">
                            <div class="shop-image">
                                <img src="https://placehold.co/580x260" alt="" />
                            </div>
                            <div class="position-absolute top-0 left-0 w-100 h-100 d-flex align-items-center justify-content-end text-start">
                                <div class="p-11 xs-p-5 w-55 md-w-50 xs-w-200px">
                                    <h5 class="alt-font text-dark-gray ls-minus-1px mb-25px xs-mb-20px">Florence <span class="fw-700">compact</span></h5>
                                    <a href="demo-decor-store-shop.html" class="btn btn-dark-gray btn-small btn-switch-text btn-round-edge btn-box-shadow">
                                        <span>
                                            <span class="btn-double-text" data-text="Explore category">Explore category</span> 
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div> 
                    </li>
                    <!-- end shop item --> 
                </ul>
            </div>
        </div>
    </div>
</section> 
<!-- end section -->
<!-- start section -->
<section class="pt-0 pb-0">
    <div class="container"> 
        <div class="row mb-4">
            <div class="col-12 tab-style-04">
                <ul class="nav nav-tabs border-0 justify-content-center text-uppercase fw-600 mb-50px sm-mb-20px alt-font fs-32 ls-minus-05px text-transform-none" data-anime='{ "el": "childs", "translateY": [50, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                    <li class="nav-item"><a data-bs-toggle="tab" href="#tab_five1" class="nav-link active">Best sellers<span class="tab-border bg-dark-gray h-2px"></span></a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_five2">New arrivals<span class="tab-border bg-dark-gray h-2px"></span></a></li> 
                </ul>
                <div class="tab-content">
                    <!-- start tab content -->
                    <div class="tab-pane fade in active show" id="tab_five1">
                        <div class="row">
                            <div class="col-12 filter-content">
                                <ul class="shop-boxed shop-wrapper grid grid-4col xxl-grid-5col xl-grid-4col lg-grid-3col md-grid-2col sm-grid-2col xs-grid-1col gutter-large text-center" data-anime='{ "el": "childs", "translateY": [50, 0], "opacity": [0,1], "duration": 600, "delay":150, "staggervalue": 150, "easing": "easeOutQuad" }'>
                                    <li class="grid-sizer"></li>
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" />
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div>
                                                </a> 
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Table clock</a>
                                                <div class="fw-500 fs-15 lh-normal"><del>$30.00</del>$23.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" /> 
                                                    <span class="lable new">New</span>
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div>
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Wood stool</a>
                                                <div class="fw-500 fs-15 lh-normal">$54.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" /> 
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div>
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Ceramic mug</a>
                                                <div class="fw-500 fs-15 lh-normal"><del>$20.00</del>$15.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" /> 
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div>
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Decorative plants</a>
                                                <div class="fw-500 fs-15 lh-normal"><del>$30.00</del>$35.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" /> 
                                                    <span class="lable new">New</span>
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div>
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Ceramic pot</a>
                                                <div class="fw-500 fs-15 lh-normal">$23.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" /> 
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div>
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Ceramic plate</a>
                                                <div class="fw-500 fs-15 lh-normal"><del>$25.00</del>$15.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" />
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div>
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Ceramic container</a>
                                                <div class="fw-500 fs-15 lh-normal">$35.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" />
                                                    <span class="lable hot">Hot</span>
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div>
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Design wall clock</a>
                                                <div class="fw-500 fs-15 lh-normal">$19.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- end tab content -->
                    <!-- start tab content -->
                    <div class="tab-pane fade in" id="tab_five2">
                        <div class="row">
                            <div class="col-12 filter-content">
                                <ul class="shop-boxed shop-wrapper grid grid-4col xxl-grid-5col xl-grid-4col lg-grid-3col md-grid-2col sm-grid-2col xs-grid-1col gutter-large text-center">
                                    <li class="grid-sizer"></li>
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" />
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div>
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Ceramic pot</a>
                                                <div class="fw-500 fs-15 lh-normal">$23.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" />
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div>
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Ceramic plate</a>
                                                <div class="fw-500 fs-15 lh-normal">$15.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" />
                                                    <span class="lable new">New</span>
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Ceramic container</a>
                                                <div class="fw-500 fs-15 lh-normal">$35.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" />
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Design wall clock</a>
                                                <div class="fw-500 fs-15 lh-normal">$19.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" />
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Table clock</a>
                                                <div class="fw-500 fs-15 lh-normal">$23.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" />
                                                    <span class="lable new">New</span>
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Wood stool</a>
                                                <div class="fw-500 fs-15 lh-normal">$54.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" /> 
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div>
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Ceramic mug</a>
                                                <div class="fw-500 fs-15 lh-normal">$15.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                    <!-- start shop item -->
                                    <li class="grid-item">
                                        <div class="shop-box pb-25px">
                                            <div class="shop-image">
                                                <a href="demo-decor-store-single-product.html">
                                                    <img src="https://placehold.co/600x700" alt="" /> 
                                                    <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div>
                                                </a>
                                                <div class="shop-hover d-flex justify-content-center">
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                                    <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                                </div>
                                            </div>
                                            <div class="shop-footer text-center pt-20px">
                                                <a href="demo-decor-store-single-product.html" class="text-dark-gray fs-17 alt-font fw-600">Decorative plants</a>
                                                <div class="fw-500 fs-15 lh-normal">$35.00</div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- end shop item -->
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- end tab content -->  
                </div>
            </div>
        </div>
    </div>
</section>
<!-- end section -->
<!-- start section -->
<section class="pt-0" data-anime='{ "translateX": [0, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
    <div class="container-fluid ps-8 pe-8"> 
        <div class="row position-relative">
            <div class="col swiper text-cente feather-shadow" data-slider-options='{ "slidesPerView": "auto", "spaceBetween":0, "centeredSlides": true, "speed": 8000, "loop": true, "pagination": { "el": ".slider-four-slide-pagination-2", "clickable": false }, "allowTouchMove": false, "autoplay": { "delay":1, "disableOnInteraction": false }, "navigation": { "nextEl": ".slider-four-slide-next-2", "prevEl": ".slider-four-slide-prev-2" }, "keyboard": { "enabled": true, "onlyInViewport": true }, "effect": "slide" }'>
                <div class="swiper-wrapper pb-20px swiper-width-auto marquee-slide">
                    <!-- start client item -->
                    <div class="swiper-slide">
                        <div class="fs-50 text-dark-gray alt-font fw-700 ls-minus-1px"><span class="w-15px h-15px border border-2 border-radius-100 border-color-medium-gray d-inline-block align-middle ms-50px me-50px"></span> Pay with multiple credit cards</div>
                    </div>
                    <!-- end client item --> 
                    <!-- start client item -->
                    <div class="swiper-slide">
                        <div class="fs-50 text-dark-gray alt-font fw-700 ls-minus-1px"><span class="w-15px h-15px border border-2 border-radius-100 border-color-medium-gray d-inline-block align-middle ms-50px me-50px"></span> Get offer up to 50% on modern furniture</div>
                    </div>
                    <!-- end client item --> 
                    <!-- start client item -->
                    <div class="swiper-slide">
                        <div class="fs-50 text-dark-gray alt-font fw-700 ls-minus-1px"><span class="w-15px h-15px border border-2 border-radius-100 border-color-medium-gray d-inline-block align-middle ms-50px me-50px"></span> Free shipping for orders over $130</div>
                    </div>
                    <!-- end client item -->  
                    <!-- start client item -->
                    <div class="swiper-slide">
                        <div class="fs-50 text-dark-gray alt-font fw-700 ls-minus-1px"><span class="w-15px h-15px border border-2 border-radius-100 border-color-medium-gray d-inline-block align-middle ms-50px me-50px"></span> Pay with multiple credit cards</div>
                    </div>
                    <!-- end client item --> 
                    <!-- start client item -->
                    <div class="swiper-slide">
                        <div class="fs-50 text-dark-gray alt-font fw-700 ls-minus-1px"><span class="w-15px h-15px border border-2 border-radius-100 border-color-medium-gray d-inline-block align-middle ms-50px me-50px"></span> Get offer up to 50% on modern furniture</div>
                    </div>
                    <!-- end client item --> 
                    <!-- start client item -->
                    <div class="swiper-slide">
                        <div class="fs-50 text-dark-gray alt-font fw-700 ls-minus-1px"><span class="w-15px h-15px border border-2 border-radius-100 border-color-medium-gray d-inline-block align-middle ms-50px me-50px"></span> Free shipping for orders over $130</div>
                    </div>
                    <!-- end client item -->   
                </div> 
            </div>  
        </div>  
    </div>
</section>
<!-- end section -->
<!-- start section -->
<section class="py-0 overflow-hidden">
    <div class="container-fluid p-0">
        <div class="row g-0">
            <div class="col-md-8 cover-background" style="background-image:url('https://placehold.co/1280x640')" data-anime='{ "translateX": [-50, 0], "opacity": [0,1], "duration": 600, "delay":150, "staggervalue": 150, "easing": "easeOutQuad" }'>
                <div class="pt-13 pb-13 pe-5 w-40 xl-w-45 lg-w-55 md-w-65 sm-w-50 xs-w-270px float-end">
                    <span class="fs-15 fw-700 text-dark-gray text-uppercase mb-20px xs-mb-15px d-inline-block text-decoration-line-bottom-medium">Save up to 50% off</span>
                    <h1 class="alt-font fw-400 text-dark-gray mb-40px lg-mb-35px xs-mb-25px ls-minus-1px">Lounge <span class="fw-700">collection</span></h1>
                    <a href="demo-decor-store-shop.html" class="btn btn-dark-gray btn-extra-large btn-switch-text btn-round-edge btn-box-shadow">
                        <span>
                            <span class="btn-double-text" data-text="Explore category">Explore category</span>
                        </span>
                    </a>
                </div>
            </div>
            <div class="col-md-4" data-anime='{ "translateX": [50, 0], "opacity": [0,1], "duration": 600, "delay":150, "staggervalue": 150, "easing": "easeOutQuad" }'>
                <div class="swiper position-relative h-100" data-slider-options='{ "slidesPerView": 1, "loop": true, "allowTouchMove": true, "autoplay": { "delay": 3000, "disableOnInteraction": false }, "navigation": { "nextEl": ".slider-one-slide-next-1", "prevEl": ".slider-one-slide-prev-1" }, "effect": "fade" }'>
                    <div class="swiper-wrapper">
                        <!-- start text slider item -->
                        <div class="swiper-slide cover-background h-100 text-center" style="background-image: url('{{ asset('modules/cms/assets/images/demo-decor-store-product-slider-bg-img.jpg') }}')">
                            <a href="demo-decor-store-single-product.html">
                                <img src="https://placehold.co/640x640" alt=""/>
                            </a>
                            <div class="position-absolute bottom-70px xs-bottom-25px w-100 left-0 text-center ls-minus-05px">
                                <a href="demo-decor-store-single-product.html" class="text-dark-gray alt-font d-inline-block fs-20 fw-600">Wooden cabinet</a>
                                <div class="d-block">
                                    <div class="d-inline-block align-middle fs-18 fw-600 text-dark-gray"><del class="me-10px">$50.00</del>$23.00</div>
                                </div>
                            </div>
                        </div>
                        <!-- end text slider item -->
                        <!-- start text slider item -->
                        <div class="swiper-slide cover-background h-100 text-center" style="background-image: url('{{ asset('modules/cms/assets/images/demo-decor-store-product-slider-bg-img.jpg') }}')">
                            <a href="demo-decor-store-single-product.html">
                                <img src="https://placehold.co/640x640" alt=""/>
                            </a>
                            <div class="position-absolute bottom-70px xs-bottom-25px w-100 left-0 text-center ls-minus-05px">
                                <a href="demo-decor-store-single-product.html" class="text-dark-gray alt-font fs-20 fw-600">Modern chair</a>
                                <div class="d-block">
                                    <div class="d-inline-block align-middle fs-18 fw-600 text-dark-gray"><del class="me-10px">$40.00</del>$18.00</div>
                                </div>
                            </div>
                        </div>
                        <!-- end text slider item -->
                        <!-- start text slider item -->
                        <div class="swiper-slide cover-background h-100 text-center" style="background-image: url('{{ asset('modules/cms/assets/images/demo-decor-store-product-slider-bg-img.jpg') }}')">
                            <a href="demo-decor-store-single-product.html">
                                <img src="https://placehold.co/640x640" alt=""/>
                            </a>
                            <div class="position-absolute bottom-70px xs-bottom-25px w-100 left-0 text-center ls-minus-05px">
                                <a href="demo-decor-store-single-product.html" class="text-dark-gray alt-font fs-20 fw-600">Classic stools</a>
                                <div class="d-block">
                                    <div class="d-inline-block align-middle fs-18 fw-600 text-dark-gray"><del class="me-10px">$56.00</del>$24.00</div>
                                </div>
                            </div>
                        </div>
                        <!-- end text slider item -->
                    </div>
                    <!-- start slider navigation -->
                    <div class="slider-one-slide-prev-1 swiper-button-prev slider-navigation-style-06"><i class="bi bi-arrow-left icon-extra-medium text-dark-gray"></i></div>
                    <div class="slider-one-slide-next-1 swiper-button-next slider-navigation-style-06"><i class="bi bi-arrow-right icon-extra-medium text-dark-gray"></i></div>
                    <!-- end slider navigation -->
                </div>
            </div>
        </div>
    </div>
</section>
<!-- end section -->
<!-- start section -->
<section class="half-section" data-anime='{ "translate": [0, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
    <div class="container">
        <div class="row position-relative clients-style-08">
            <div class="col swiper text-center feather-shadow" data-slider-options='{ "slidesPerView": 1, "spaceBetween":0, "speed": 3000, "loop": true, "pagination": { "el": ".slider-four-slide-pagination-2", "clickable": false }, "allowTouchMove": false, "autoplay": { "delay":0, "disableOnInteraction": false, "pauseOnMouseEnter": false}, "navigation": { "nextEl": ".slider-four-slide-next-2", "prevEl": ".slider-four-slide-prev-2" }, "keyboard": { "enabled": true, "onlyInViewport": true }, "breakpoints": { "1200": { "slidesPerView": 4 }, "992": { "slidesPerView": 3 }, "768": { "slidesPerView": 3 }, "576": { "slidesPerView": 2 } }, "effect": "slide" }'>
                <div class="swiper-wrapper marquee-slide">
                    <!-- start client item -->
                    <div class="swiper-slide">
                        <a href="#"><img src="https://placehold.co/195x50" alt="" /></a>
                    </div>
                    <!-- end client item -->
                    <!-- start client item -->
                    <div class="swiper-slide">
                        <a href="#"><img src="https://placehold.co/195x50" alt="" /></a>
                    </div>
                    <!-- end client item -->
                    <!-- start client item -->
                    <div class="swiper-slide">
                        <a href="#"><img src="https://placehold.co/195x50" alt="" /></a>
                    </div>
                    <!-- end client item -->
                    <!-- start client item -->
                    <div class="swiper-slide">
                        <a href="#"><img src="https://placehold.co/195x50" alt="" /></a>
                    </div>
                    <!-- end client item -->
                    <!-- start client item -->
                    <div class="swiper-slide">
                        <a href="#"><img src="https://placehold.co/195x50" alt="" /></a>
                    </div>
                    <!-- end client item -->
                    <!-- start client item -->
                    <div class="swiper-slide">
                        <a href="#"><img src="https://placehold.co/195x50" alt="" /></a>
                    </div>
                    <!-- end client item -->
                    <!-- start client item -->
                    <div class="swiper-slide">
                        <a href="#"><img src="https://placehold.co/195x50" alt="" /></a>
                    </div>
                    <!-- end client item -->
                    <!-- start client item -->
                    <div class="swiper-slide">
                        <a href="#"><img src="https://placehold.co/195x50" alt="" /></a>
                    </div>
                    <!-- end client item -->
                </div> 
            </div>  
        </div>
    </div>
</section>
<!-- end section -->
<!-- start section -->
<section class="pb-3 bg-gradient-very-light-gray lg-pb-40px md-pb-20px"> 
    <div class="container">
        <div class="row justify-content-center mb-25px">
            <div class="col-lg-5 text-center" data-anime='{ "el": "childs", "translateY": [50, 0], "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 150, "easing": "easeOutQuad" }'>
                <span class="text-uppercase fs-14 ls-2px fw-600">Designers ideas</span>
                <h4 class="alt-font text-dark-gray fw-700 mb-20px">The decor article</h4>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <ul class="blog-classic blog-wrapper grid-loading grid grid-4col xl-grid-4col lg-grid-3col md-grid-2col sm-grid-2col xs-grid-1col gutter-extra-large" data-anime='{ "el": "childs", "translateY": [50, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                    <li class="grid-sizer"></li>
                    <!-- start blog item -->
                    <li class="grid-item">
                        <div class="card bg-transparent border-0 h-100">
                            <div class="blog-image position-relative overflow-hidden border-radius-4px">
                                <a href="demo-decor-store-blog-single-classic.html"><img src="https://placehold.co/600x445" alt="" /></a>
                            </div>
                            <div class="card-body px-0 pt-30px pb-30px xs-pb-15px">
                                <span class="fs-13 text-uppercase d-block mb-5px fw-500"><a href="demo-decor-store-blog.html" class="text-dark-gray fw-700 categories-text">Decor</a><a href="#" class="blog-date">08 August 2023</a></span>
                                <a href="demo-decor-store-blog-single-classic.html" class="card-title alt-font fw-600 fs-17 lh-30 text-dark-gray d-inline-block w-95 sm-w-100">The best influencers to follow for sartorial inspiration</a>
                            </div>
                        </div>
                    </li>
                    <!-- end blog item --> 
                    <!-- start blog item -->
                    <li class="grid-item">
                        <div class="card bg-transparent border-0 h-100">
                            <div class="blog-image position-relative overflow-hidden border-radius-4px">
                                <a href="demo-decor-store-blog-single-classic.html"><img src="https://placehold.co/600x445" alt="" /></a>
                            </div>
                            <div class="card-body px-0 pt-30px pb-30px xs-pb-15px">
                                <span class="fs-13 text-uppercase d-block mb-5px fw-500"><a href="demo-decor-store-blog.html" class="text-dark-gray fw-700 categories-text">Design</a><a href="#" class="blog-date">12 August 2023</a></span>
                                <a href="demo-decor-store-blog-single-classic.html" class="card-title alt-font fw-600 fs-17 lh-30 text-dark-gray d-inline-block w-95 sm-w-100">Everything you need to know about decor's big night out</a>
                            </div>
                        </div>
                    </li>
                    <!-- end blog item --> 
                    <!-- start blog item -->
                    <li class="grid-item">
                        <div class="card bg-transparent border-0 h-100">
                            <div class="blog-image position-relative overflow-hidden border-radius-4px">
                                <a href="demo-decor-store-blog-single-classic.html"><img src="https://placehold.co/600x445" alt="" /></a>
                            </div>
                            <div class="card-body px-0 pt-30px pb-30px xs-pb-15px">
                                <span class="fs-13 text-uppercase d-block mb-5px fw-500"><a href="demo-decor-store-blog.html" class="text-dark-gray fw-700 categories-text">Decor</a><a href="#" class="blog-date">20 August 2023</a></span>
                                <a href="demo-decor-store-blog-single-classic.html" class="card-title alt-font fw-600 fs-17 lh-30 text-dark-gray d-inline-block w-95 sm-w-100">All the best looks & moments from the met gala 2023</a>
                            </div>
                        </div>
                    </li>
                    <!-- end blog item -->
                    <!-- start blog item -->
                    <li class="grid-item">
                        <div class="card bg-transparent border-0 h-100">
                            <div class="blog-image position-relative overflow-hidden border-radius-4px">
                                <a href="demo-decor-store-blog-single-classic.html"><img src="https://placehold.co/600x445" alt="" /></a>
                            </div>
                            <div class="card-body px-0 pt-30px pb-30px xs-pb-15px">
                                <span class="fs-13 text-uppercase d-block mb-5px fw-500"><a href="demo-decor-store-blog.html" class="text-dark-gray fw-700 categories-text">Decor</a><a href="#" class="blog-date">26 August 2023</a></span>
                                <a href="demo-decor-store-blog-single-classic.html" class="card-title alt-font fw-600 fs-17 lh-30 text-dark-gray d-inline-block w-95 sm-w-100">Find a colour palettes that reflects your passion</a>
                            </div>
                        </div>
                    </li>
                    <!-- end blog item -->
                </ul>
            </div>
        </div>
    </div>
</section>
<!-- end section -->
<!-- start section -->
<section class="border-top border-color-extra-medium-gray py-0 overflow-hidden">
    <div class="container-fluid">
        <div class="row row-cols-1 row-cols-lg-4 row-cols-sm-2 justify-content-center" data-anime='{ "el": "childs", "translateX": [50, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
            <!-- start features box item -->
            <div class="col d-flex justify-content-center icon-with-text-style-08 text-center border-end md-border-bottom xs-border-end-0 border-color-extra-medium-gray pt-45px pb-45px">
                <div class="feature-box feature-box-left-icon-middle d-inline-flex align-middle">
                    <div class="feature-box-icon me-15px">  
                        <img src="https://placehold.co/60x50" alt=""/>
                    </div>
                    <div class="feature-box-content">
                        <span class="alt-font fw-600 text-dark-gray d-block lh-24 fs-17">Free shipping</span>
                        <span>Free return & exchange</span>
                    </div>
                </div>
            </div>
            <!-- end features box item -->
            <!-- start features box item -->
            <div class="col d-flex justify-content-center icon-with-text-style-08 text-center border-end md-border-bottom md-border-end-0 border-color-extra-medium-gray pt-45px pb-45px">
                <div class="feature-box feature-box-left-icon-middle d-inline-flex align-middle">
                    <div class="feature-box-icon me-15px">  
                        <img src="https://placehold.co/60x50" alt=""/>
                    </div>
                    <div class="feature-box-content">
                        <span class="alt-font fw-600 text-dark-gray d-block lh-24 fs-17">Store locator</span>
                        <span>Find nearest store</span>
                    </div>
                </div>
            </div>
            <!-- end features box item -->
            <!-- start features box item -->
            <div class="col d-flex justify-content-center icon-with-text-style-08 text-center border-end xs-border-bottom xs-border-end-0 border-color-extra-medium-gray pt-45px pb-45px">
                <div class="feature-box feature-box-left-icon-middle d-inline-flex align-middle">
                    <div class="feature-box-icon me-15px">  
                        <img src="https://placehold.co/60x50" alt=""/>
                    </div>
                    <div class="feature-box-content">
                        <span class="alt-font fw-600 text-dark-gray d-block lh-24 fs-17">Secure payment</span>
                        <span>100% secure method</span>
                    </div>
                </div>
            </div>
            <!-- end features box item -->
            <!-- start features box item -->
            <div class="col d-flex justify-content-center icon-with-text-style-08 text-center pt-45px pb-45px">
                <div class="feature-box feature-box-left-icon-middle d-inline-flex align-middle">
                    <div class="feature-box-icon me-15px">  
                        <img src="https://placehold.co/60x50" alt=""/>
                    </div>
                    <div class="feature-box-content">
                        <span class="alt-font fw-600 text-dark-gray d-block lh-24 fs-17">Online support</span>
                        <span>24/7 support center</span>
                    </div>
                </div>
            </div>
            <!-- end features box item -->
        </div>
    </div>
</section>
<!-- end section -->

@endsection
@section('javascript')
@endsection