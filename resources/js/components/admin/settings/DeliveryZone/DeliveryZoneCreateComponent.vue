<template>
	<div>
		<button type="button" class="db-btn h-[38px] text-white bg-primary" @click="openModal">
			<i class="lab lab-add-circle"></i>
			<span class="ml-1 capitalize">{{ $t("button.add_new") }}</span>
		</button>
		<div id="db-modal" class="modal">
			<div class="modal-dialog">
				<div class="flex items-center justify-between gap-4 py-3.5 px-4 border-b border-slate-100">
					<h3 class="text-lg font-semibold capitalize">
						{{ isEditing ? $t("label.edit") : $t("label.add") }} {{ $t("menu.delivery_zone") }}
					</h3>
					<button class="modal-close fa-regular fa-circle-xmark" @click="reset"></button>
				</div>
				<div class="p-4 space-y-4">
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
						/>
						<small class="db-field-alert" v-if="errors.branch_id">{{ errors.branch_id[0] }}</small>
					</div>
					<div>
						<label class="db-field-title">{{ $t("label.zone_name") }}</label>
						<input type="text" class="db-field-control" v-model="local.form.name" placeholder="e.g., Zone 1 - City Center" />
						<small class="db-field-alert" v-if="errors.name">{{ errors.name[0] }}</small>
					</div>
					<div>
						<label class="db-field-title required">{{ $t("label.max_distance_km") || "Max Distance (km)" }}</label>
						<input type="number" min="0" step="0.01" class="db-field-control" v-model="local.form.max_distance_km" placeholder="e.g., 5.0" />
						<small class="db-field-alert" v-if="errors.max_distance_km">{{ errors.max_distance_km[0] }}</small>
					</div>
					<div>
						<label class="db-field-title required">{{ $t("label.delivery_price") }}</label>
						<input type="number" min="0" step="0.01" class="db-field-control" v-model="local.form.delivery_price" />
						<small class="db-field-alert" v-if="errors.delivery_price">{{ errors.delivery_price[0] }}</small>
					</div>
					<div>
						<label class="db-field-title">{{ $t("label.sort_order") || "Sort Order" }}</label>
						<input type="number" min="0" class="db-field-control" v-model="local.form.sort_order" placeholder="0" />
						<small class="text-xs text-gray-500 mt-1">{{ $t("label.sort_order_help") || "Lower numbers appear first (e.g., 0-5km = 1, 5-10km = 2)" }}</small>
						<small class="db-field-alert" v-if="errors.sort_order">{{ errors.sort_order[0] }}</small>
					</div>
					<div>
						<label class="db-field-title required">{{ $t("label.status") }}</label>
						<select class="db-field-control" v-model="local.form.status">
							<option :value="enums.statusEnum.ACTIVE">{{ $t("label.active") }}</option>
							<option :value="enums.statusEnum.INACTIVE">{{ $t("label.inactive") }}</option>
						</select>
						<small class="db-field-alert" v-if="errors.status">{{ errors.status[0] }}</small>
					</div>
					<div class="flex justify-end gap-2 pt-2">
						<button class="db-btn outline" type="button" @click="reset">{{ $t("button.cancel") }}</button>
						<button class="db-btn primary" type="button" @click="save">{{ isEditing ? $t("button.update") : $t("button.save") }}</button>
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

export default {
	name: "DeliveryZoneCreateComponent",
	props: ["props"],
	data() {
		return {
			local: JSON.parse(JSON.stringify(this.props)),
			enums: { statusEnum: statusEnum },
			errors: {},
			loading: { isActive: false },
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
	},
	watch: {
		props: {
			deep: true,
			handler(nv) {
				this.local = JSON.parse(JSON.stringify(nv));
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
			appService.modalShow("#db-modal");
		},
		reset: function () {
			appService.modalHide("#db-modal");
			this.errors = {};
			this.local.form = {
				branch_id: null,
				name: "",
				max_distance_km: "",
				delivery_price: "",
				sort_order: 0,
				status: statusEnum.ACTIVE,
			};
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


