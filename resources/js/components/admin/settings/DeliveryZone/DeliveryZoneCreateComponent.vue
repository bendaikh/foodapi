<template>
	<div>
		<button type="button" class="db-btn h-[38px] text-white bg-primary" @click="openModal">
			<i class="lab lab-add-circle"></i>
			<span class="ml-1 capitalize">{{ $t("button.add_new") }}</span>
		</button>
		<div id="db-modal" class="modal">
			<div class="modal-dialog" style="max-width: 800px;">
				<div class="flex items-center justify-between gap-4 py-3.5 px-4 border-b border-slate-100">
					<h3 class="text-lg font-semibold capitalize text-primary">
						{{ $t("label.zone_delivery_settings") || "Zone Delivery Settings" }}
					</h3>
					<button class="modal-close fa-regular fa-circle-xmark" @click="reset"></button>
				</div>
				<div class="p-4 space-y-4 max-h-[80vh] overflow-y-auto">
					<!-- Zone Type -->
					<div>
						<label class="db-field-title">{{ $t("label.type") || "Type" }}</label>
						<select class="db-field-control" v-model="local.form.zone_type" disabled>
							<option value="circle">{{ $t("label.circle") || "Circle" }}</option>
						</select>
					</div>
					
					<!-- Branch Selection -->
					<div>
						<label class="db-field-title required">{{ $t("label.branch") }}</label>
						<vue-select 
							class="db-field-control f-b-custom-select" 
							v-model="local.form.branch_id" 
							:options="branches" 
							label-by="name" 
							value-by="id"
							:closeOnSelect="true" 
							:searchable="true" 
							:clearOnClose="true" 
							placeholder="--" 
							search-placeholder="--"
							@update:modelValue="onBranchChange"
						/>
						<small class="db-field-alert" v-if="errors.branch_id">{{ errors.branch_id[0] }}</small>
					</div>
					
					<!-- Map Component (shown when branch is selected) -->
					<div v-if="local.form.branch_id && showMap">
						<DeliveryZoneMapComponent 
							:branchId="local.form.branch_id"
							:initialRadius="parseFloat(local.form.max_distance_km) || 1.5"
							@radiusChange="onRadiusChange"
						/>
					</div>
					
					<!-- Zone Name -->
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<label class="db-field-title">{{ $t("label.zone_name") }}</label>
							<input type="text" class="db-field-control" v-model="local.form.name" placeholder="e.g., Zone 1 - City Center" />
							<small class="db-field-alert" v-if="errors.name">{{ errors.name[0] }}</small>
						</div>
						
						<!-- Max Distance (auto-filled from map) -->
						<div>
							<label class="db-field-title required">{{ $t("label.max_distance_km") || "Max Distance (km)" }}</label>
							<div class="relative">
								<input 
									type="number" 
									min="0" 
									step="0.01" 
									class="db-field-control pr-12" 
									v-model="local.form.max_distance_km" 
									placeholder="e.g., 5.0"
									@input="onDistanceManualInput"
								/>
								<span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">km</span>
							</div>
							<small class="db-field-alert" v-if="errors.max_distance_km">{{ errors.max_distance_km[0] }}</small>
						</div>
					</div>
					
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<!-- Delivery Price -->
						<div>
							<label class="db-field-title required">{{ $t("label.delivery_price") }}</label>
							<input type="number" min="0" step="0.01" class="db-field-control" v-model="local.form.delivery_price" />
							<small class="db-field-alert" v-if="errors.delivery_price">{{ errors.delivery_price[0] }}</small>
						</div>
						
						<!-- Sort Order -->
						<div>
							<label class="db-field-title">{{ $t("label.sort_order") || "Sort Order" }}</label>
							<input type="number" min="0" class="db-field-control" v-model="local.form.sort_order" placeholder="0" />
							<small class="text-xs text-gray-500 mt-1">{{ $t("label.sort_order_help") || "Lower numbers appear first" }}</small>
							<small class="db-field-alert" v-if="errors.sort_order">{{ errors.sort_order[0] }}</small>
						</div>
					</div>
					
					<!-- Status -->
					<div>
						<label class="db-field-title required">{{ $t("label.status") }}</label>
						<select class="db-field-control" v-model="local.form.status">
							<option :value="enums.statusEnum.ACTIVE">{{ $t("label.active") }}</option>
							<option :value="enums.statusEnum.INACTIVE">{{ $t("label.inactive") }}</option>
						</select>
						<small class="db-field-alert" v-if="errors.status">{{ errors.status[0] }}</small>
					</div>
					
					<!-- Action Buttons -->
					<div class="flex justify-center gap-3 pt-4 border-t">
						<button class="db-btn h-[42px] px-8 text-white bg-primary rounded-full" type="button" @click="save">
							{{ $t("button.save") || "SAVE" }}
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import appService from "../../../../services/appService";
import alertService from "../../../../services/alertService";
import statusEnum from "../../../../enums/modules/statusEnum";
import DeliveryZoneMapComponent from "./DeliveryZoneMapComponent.vue";

export default {
	name: "DeliveryZoneCreateComponent",
	components: {
		DeliveryZoneMapComponent
	},
	props: ["props"],
	data() {
		return {
			local: JSON.parse(JSON.stringify(this.props)),
			enums: { statusEnum: statusEnum },
			errors: {},
			loading: { isActive: false },
			showMap: false
		};
	},
	computed: {
		isEditing() {
			return this.$store.getters["deliveryZone/temp"].isEditing;
		},
		branches() {
			return this.$store.getters["branch/lists"] || [];
		},
	},
	mounted() {
		this.loadBranches();
		// Set default zone type
		if (!this.local.form.zone_type) {
			this.local.form.zone_type = "circle";
		}
	},
	watch: {
		props: {
			deep: true,
			handler(nv) {
				this.local = JSON.parse(JSON.stringify(nv));
				if (!this.local.form.zone_type) {
					this.local.form.zone_type = "circle";
				}
			},
		},
	},
	methods: {
		loadBranches: function () {
			this.loading.isActive = true;
			this.$store
				.dispatch("branch/lists", {
					status: statusEnum.ACTIVE,
					order_column: "id",
					order_type: "asc",
				})
				.then(() => {
					this.loading.isActive = false;
				})
				.catch(() => {
					this.loading.isActive = false;
				});
		},
		openModal: function () {
			this.$store.dispatch("deliveryZone/reset");
			this.loadBranches();
			this.showMap = false;
			appService.modalShow("#db-modal");
		},
		reset: function () {
			appService.modalHide("#db-modal");
			this.errors = {};
			this.showMap = false;
			this.local.form = {
				branch_id: null,
				name: "",
				zone_type: "circle",
				max_distance_km: "",
				delivery_price: "",
				sort_order: 0,
				status: statusEnum.ACTIVE,
			};
		},
		onBranchChange: function (branchId) {
			// Reset and show map when branch changes
			this.showMap = false;
			this.$nextTick(() => {
				if (branchId) {
					this.showMap = true;
				}
			});
		},
		onRadiusChange: function (data) {
			// Update the max_distance_km when radius changes on map
			this.local.form.max_distance_km = data.radiusKm.toFixed(2);
		},
		onDistanceManualInput: function () {
			// This will trigger the map to update via the watch on initialRadius
			// The map component watches for changes in initialRadius
		},
		save: function () {
			this.errors = {};
			
			if (!this.local.form.branch_id) {
				alertService.error(this.$t("validation.branch_required") || "Branch is required");
				return;
			}
			if (!this.local.form.max_distance_km || isNaN(this.local.form.max_distance_km) || parseFloat(this.local.form.max_distance_km) <= 0) {
				alertService.error(this.$t("validation.max_distance_required") || "Max distance (km) is required and must be greater than 0");
				return;
			}
			if (!this.local.form.delivery_price || isNaN(this.local.form.delivery_price) || parseFloat(this.local.form.delivery_price) < 0) {
				alertService.error(this.$t("validation.delivery_price_numeric_gt_zero") || "Delivery price must be numeric and >= 0");
				return;
			}
			
			this.$store
				.dispatch("deliveryZone/save", { form: this.local.form, search: this.local.search })
				.then(() => {
					appService.modalHide("#db-modal");
					this.reset();
					alertService.success(this.$t("message.delivery_zone_saved") || "Delivery zone saved successfully");
				})
				.catch((err) => {
					if (typeof err.response?.data?.errors === "object") {
						this.errors = err.response.data.errors;
						Object.values(err.response.data.errors).forEach((arr) => alertService.error(arr[0]));
					} else if (err.response?.data?.message) {
						alertService.error(err.response.data.message);
					}
				});
		},
	},
};
</script>

<style scoped>
.modal-dialog {
	width: 95%;
}

@media (min-width: 768px) {
	.modal-dialog {
		width: 800px;
	}
}
</style>
