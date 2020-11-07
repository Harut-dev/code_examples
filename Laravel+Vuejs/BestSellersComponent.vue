<template>
    <div class="col-12 best_seller_comp" v-if="bestSellers && bestSellers.length > 0">
        <div class="home-left-side text-center text-lg-left">
            <div class="single-block">
                <h3 class="home-sidebar-title">
                    {{$t('best_sellers')}}
                </h3>
              <swiper :options="swiperOption" class="swiper-container discount_product_slider product-slider">
                <swiper-slide class="swiper-slide" v-for="(bestSeller, index) in bestSellers" :key="index">
                  <div class="product-card card-style-list">
                    <div class="card-image">
                      <img :src="bestSeller.product.image ? resizedImage(`/storage/images/products${ bestSeller.product.image }`, 'md') : `/images/default/default.png`"
                           :alt="bestSeller.name"
                      >
                    </div>
                    <div class="product-card--body">
                      <div class="product-header">
                        <h3><a :href='`product/detail/${bestSeller.product.id}/${ textToValidRoute(bestSeller.name) }`'>
                          {{bestSeller.name}}
                        </a></h3>
                      </div>
                      <div class="price-block">
                        <span v-if="bestSeller.product.discount_id" class="price">
                          {{getSellerValue(bestSeller)}}
                          <span class="icon-dram"></span>
                        </span>
                        <del class="price-old">
                          {{bestSeller.product.price}}
                          <span class="icon-dram gray-icon-dram"></span>
                        </del>
                        <span v-if="bestSeller.product.discount_id" class="price-discount">{{bestSeller.product.discount.percentage}} %</span>
                      </div>
                    </div>
                  </div>
                </swiper-slide>
                <div class="swiper-pagination_discount" slot="pagination"></div>
              </swiper>
            </div>
        </div>
    </div>
</template>

<script>
    import 'swiper/dist/css/swiper.css';
    import {swiper, swiperSlide} from 'vue-awesome-swiper';
    import * as actions from '../../store/action-types';
    import { EventBus } from "../../event-bus";
    import { getResizedImage, textToValidRoute } from "../../helpers";

    export default {
        name: "BestSellersComponent",
        components: {
            swiper,
            swiperSlide,
        },
        data() {
            return {
                swiperOption: {
                    spaceBetween: 30,
                    centeredSlides: false,
                    autoplay: {
                      delay: 8000,
                    },
                    slidesPerView: 3,
                    slidesPerGroup: 3,
                    loop: true,
                    loopFillGroupWithBlank: true,
                    slidesPerColumn: 1,
                    pagination: {
                        el: '.swiper-pagination_discount',
                        clickable: true
                    },
                    breakpoints: {
                        490: {
                            slidesPerView: 1,
                            slidesPerGroup: 1,
                            loop: false
                        },
                        577: {
                            slidesPerView: 2,
                            slidesPerColumn: 1,
                            slidesPerGroup: 2,
                            loop: false
                        },
                        768: {
                            slidesPerView: 2,
                            slidesPerColumn: 1,
                            slidesPerGroup: 2,
                            loop: false
                        },
                        992: {
                            slidesPerView: 2,
                            slidesPerGroup: 2,
                            loop: false
                        },
                        1200: {
                            slidesPerView: 3,
                        },
                    }
                },
                descending: false,
                page: 1,
                rowsPerPage: 15,
                sortBy: 'created_at',
                bestSellers: [],
            }
        },
        methods: {
            getSellerValue (bestSeller) {
                return Math.round(bestSeller.product.discounts_price)
            },
            languageChanged() {
                this.getBestSellers();
            },
            getBestSellers() {
                this.$store.dispatch(actions.GET_BEST_SELLERS, {
                    sortBy: this.sortBy,
                    descending: this.descending,
                    page: this.page,
                    rowsPerPage: this.rowsPerPage
                }).then(res => {
                    this.bestSellers = [];
                    Vue.nextTick(() => {
                        this.bestSellers = res.items;
                        this.$store.commit('SET_SELLERS_COUNT', this.bestSellers.length)
                    })
                }).catch(console.error)
            },
            resizedImage(path, resized){
                return getResizedImage(path, resized);
            },
            textToValidRoute(str){
              return textToValidRoute(str);
            },
        },
        created() {
            EventBus.$on('language-changed', this.languageChanged);
            this.getBestSellers();
        }
    }
</script>
