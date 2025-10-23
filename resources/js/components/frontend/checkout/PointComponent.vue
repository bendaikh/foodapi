<template>
        <div v-if="points.is_point_applicable" class=" mb-4 w-full flex items-center gap-3.5 py-2 px-4 rounded-lg shadow-coupon text-heading bg-white transition hover:text-primary flex items-center gap-3 mb-4">
            <!-- <i class="lab-ticket-discount lab-font-size-30 text-3xl text-[#008BBA]"></i> -->
            <dl class="flex-auto" v-if="!pointSwitcher">
                <dt class="text-sm font-medium capitalize mb-1"> {{ $t('message.apply_points') }}</dt> 
                <dd class="text-xs text-secondary">
                    {{ points.user_points }} {{ $t('message.points_available') }}.
                     {{points.applicable_points }} {{ $t('message.points_will_be_applied_for') }}  {{points.currency_point_discount_amount }} {{ $t('message.discount') }}.
                </dd>
            </dl>
            <dl class="flex-auto" v-else>
                <dt class="text-sm font-medium capitalize mb-1 text-primary">{{ $t('message.points_applied') }}</dt>
                <dd class="text-xs text-secondary">
                    {{points.applicable_points }} {{ $t('message.points_applied_for') }}  {{points.currency_point_discount_amount }} {{ $t('message.discount') }}. {{ points.user_points - points.applicable_points }} {{ $t('message.points_remaining') }}
                </dd>
            </dl>
            <label for="switcher" class="cs-custom-switcher">
                <input type="checkbox" id="switcher" v-model="pointSwitcher" @change="applyPoints">
            </label>
        </div>
</template>
<script>

import appService from "../../../services/appService";
import alertService from "../../../services/alertService";
import taxTypeEnum from "../../../enums/modules/taxTypeEnum";


export default {
    name: "PointComponent",
    props:['subtotal'],
    emits:['applyPoints'],
    data() {
        return {
            taxTypeEnum: taxTypeEnum,
            points:0,
            error: "",
            pointSwitcher:false,
            points: {
                'is_point_applicable': false,
                'user_points': 0,
                'applicable_points': 0,
                'point_discount_amount': 0,
                'currency_point_discount_amount': 0
            }
        }
    },
    computed: {
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
        cartPoint: function () {
            return this.$store.getters['frontendCart/point'];
        }
    },
    mounted() {
        this.$emit('applyPoints', this.cartPoint);

        this.pointSwitcher = this.cartPoint.point_discount_amount > 0;
        
        this.$store.dispatch("frontendPoint/userPointsChecking").then(
            (res) => {
                if(res.data.data.points){
                    this.points.is_point_applicable = res.data.data.is_point_applicable;
                    this.points.user_points = res.data.data.user_points;
                    this.points.applicable_points = res.data.data.applicable_points;
                    this.points.point_discount_amount = res.data.data.point_discount_amount;
                    this.points.currency_point_discount_amount = res.data.data.currency_point_discount_amount;
                }
            }
        ).catch();
    },
    methods: {
        applyPoints: function() {
            if(+this.points.point_discount_amount >= +this.subtotal){
                this.pointSwitcher = false;
                alertService.info(this.$t('message.subtotal_is_less_than_discount_amount'));
                this.$emit('applyPoints', {'applicable_points':0,'point_discount_amount':0})
                this.$store.dispatch("frontendCart/point", {});
                return false;
            }

            if(this.pointSwitcher){
                this.$emit('applyPoints', {'applicable_points':this.points.applicable_points,'point_discount_amount':this.points.point_discount_amount})
                this.$store.dispatch("frontendCart/point", {'applicable_points':this.points.applicable_points,'point_discount_amount':this.points.point_discount_amount});
            }else{
                this.$emit('applyPoints', {'applicable_points':0,'point_discount_amount':0})
                this.$store.dispatch("frontendCart/point", {});
            }
        }

    },
    watch: {
        subtotal(newVal,oldVal) {
            if(+this.points.point_discount_amount >= +this.subtotal){
                this.pointSwitcher = false;
                this.$emit('applyPoints', {'applicable_points':0,'point_discount_amount':0})
                this.$store.dispatch("frontendCart/point", {});
            }
        }
    }
}
</script>
