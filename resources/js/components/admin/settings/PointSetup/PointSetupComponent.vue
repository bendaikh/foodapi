<template>
    <LoadingComponent :props="loading" />
    <div id="order_setup" class="db-card db-tab-div active">
        <div class="db-card-header">
            <h3 class="db-card-title">{{ $t('menu.point_setup') }}</h3>
        </div>
        <div class="db-card-body">
            <form @submit.prevent="save">
                <fieldset class="p-4 mb-6 border border-[#DBDEE0]">
                    <legend class="py-1.5 px-4 text-base font-semibold capitalize border border-[#DBDEE0] text-primary">
                        {{ $t('menu.points') }}
                    </legend>
                    <div class="form-row">
                        <div class="form-col-12 sm:form-col-6">
                            <label for="point_setup_each_currency_to_points" class="db-field-title required">
                                {{ $t("label.each_currency_to_points") }}
                                <span class="text-primary"> ( Ex: 1$ = 1 {{ $t("label.points") }} ) </span>
                            </label>
                            <input v-on:keypress="onlyNumber($event)"
                                v-model="form.point_setup_each_currency_to_points"
                                v-bind:class="errors.point_setup_each_currency_to_points ? 'invalid' : ''" type="text"
                                id="point_setup_each_currency_to_points" class="db-field-control" />
                            <small class="db-field-alert" v-if="errors.point_setup_each_currency_to_points">{{
                                errors.point_setup_each_currency_to_points[0]
                            }}</small>
                        </div>
                        <div class="form-col-12 sm:form-col-6">
                            <label for="point_setup_points_for_each_currency" class="db-field-title required">
                                {{ $t("label.points_for_each_currency") }}
                                <span class="text-primary"> ( Ex: 50 {{ $t("label.points") }} = 1$  ) </span>
                            </label>
                            <input v-on:keypress="onlyNumber($event)"
                                v-model="form.point_setup_points_for_each_currency"
                                v-bind:class="errors.point_setup_points_for_each_currency ? 'invalid' : ''" type="text"
                                id="point_setup_points_for_each_currency" class="db-field-control" />
                            <small class="db-field-alert" v-if="errors.point_setup_points_for_each_currency">{{
                                errors.point_setup_points_for_each_currency[0]
                            }}</small>
                        </div>
                        <div class="form-col-12 sm:form-col-6">
                            <label for="point_setup_minimum_applicable_points_for_each_order" class="db-field-title required">
                                {{ $t("label.minimum_eligible_points_for_usage") }}
                            </label>
                            <input v-on:keypress="onlyNumber($event)"
                                v-model="form.point_setup_minimum_applicable_points_for_each_order"
                                v-bind:class="errors.point_setup_minimum_applicable_points_for_each_order ? 'invalid' : ''" type="text"
                                id="point_setup_minimum_applicable_points_for_each_order" class="db-field-control" />
                            <small class="db-field-alert" v-if="errors.point_setup_minimum_applicable_points_for_each_order">{{
                                errors.point_setup_minimum_applicable_points_for_each_order[0]
                            }}</small>
                        </div>
                        <div class="form-col-12 sm:form-col-6">
                            <label for="point_setup_maximum_applicable_points_for_each_order" class="db-field-title required">
                                {{ $t("label.maximum_applicable_points_for_per_order") }}
                            </label>
                            <input v-on:keypress="onlyNumber($event)"
                                v-model="form.point_setup_maximum_applicable_points_for_each_order"
                                v-bind:class="errors.point_setup_maximum_applicable_points_for_each_order ? 'invalid' : ''" type="text"
                                id="point_setup_maximum_applicable_points_for_each_order" class="db-field-control" />
                            <small class="db-field-alert" v-if="errors.point_setup_maximum_applicable_points_for_each_order">{{
                                errors.point_setup_maximum_applicable_points_for_each_order[0]
                            }}</small>
                        </div>
                    </div>
                </fieldset>
                <button type="submit" class="db-btn text-white bg-primary">
                    <i class="lab lab-save"></i>
                    <span>{{ $t("button.save") }}</span>
                </button>
            </form>
        </div>
    </div>
</template>

<script>

import LoadingComponent from "../../components/LoadingComponent";
import alertService from "../../../../services/alertService";
import appService from "../../../../services/appService";

export default {
    name: "PointSetupComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: {
                isActive: false
            },
            form: {
                point_setup_each_currency_to_points: null,
                point_setup_points_for_each_currency: null,
                point_setup_minimum_applicable_points_for_each_order: null,
                point_setup_maximum_applicable_points_for_each_order: null,
            },
            errors: {}
        }
    },
    computed: {
    },
    mounted() {
        try {
            this.loading.isActive = true;
            this.$store.dispatch('pointSetup/lists').then(res => {
                this.form = {
                    point_setup_each_currency_to_points: res.data.data.point_setup_each_currency_to_points,
                    point_setup_points_for_each_currency: res.data.data.point_setup_points_for_each_currency,
                    point_setup_minimum_applicable_points_for_each_order: res.data.data.point_setup_minimum_applicable_points_for_each_order,
                    point_setup_maximum_applicable_points_for_each_order: res.data.data.point_setup_maximum_applicable_points_for_each_order,
                }
                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
            });
        } catch (err) {
            this.loading.isActive = false;
        }
    },
    methods: {
        onlyNumber(e) {
            return appService.onlyNumber(e);
        },
        save: function () {
            try {
                this.loading.isActive = true;
                this.$store.dispatch("pointSetup/save", this.form).then((res) => {
                    this.loading.isActive = false;
                    alertService.successFlip(res.config.method === "put" ?? 0, this.$t("menu.point_setup"));
                    this.errors = {};
                }).catch((err) => {
                    this.loading.isActive = false;
                    this.errors = err.response.data.errors;
                });
            } catch (err) {
                this.loading.isActive = false;
                alertService.error(err);
            }
        },
    }
}
</script>
