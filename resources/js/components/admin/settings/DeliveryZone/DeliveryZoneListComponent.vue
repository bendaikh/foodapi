<template>
	<LoadingComponent :props="loading" />
	<div class="db-card db-tab-div active">
		<div class="db-card-header border-none">
			<h3 class="db-card-title">{{ $t("menu.delivery_zones") }}</h3>
			<div class="db-card-filter">
				<TableLimitComponent :method="list" :search="props.search" :page="paginationPage" />
				<DeliveryZoneCreateComponent :props="props" />
			</div>
		</div>
		<div class="db-table-responsive">
			<table class="db-table stripe">
				<thead class="db-table-head">
					<tr class="db-table-head-tr">
						<th class="db-table-head-th">{{ $t("label.branch") }}</th>
						<th class="db-table-head-th">{{ $t("label.zone_name") || "Zone Name" }}</th>
						<th class="db-table-head-th">{{ $t("label.max_distance_km") || "Max Distance (km)" }}</th>
						<th class="db-table-head-th">{{ $t("label.delivery_price") }}</th>
						<th class="db-table-head-th">{{ $t("label.status") }}</th>
						<th class="db-table-head-th">{{ $t("label.action") }}</th>
					</tr>
				</thead>
				<tbody class="db-table-body" v-if="zones.length > 0">
					<tr class="db-table-body-tr" v-for="zone in zones" :key="zone.id">
						<td class="db-table-body-td">
							{{ zone.branch?.name || "N/A" }}
						</td>
						<td class="db-table-body-td">
							{{ zone.name || zone.zone_name || "-" }}
						</td>
						<td class="db-table-body-td">
							{{ zone.max_distance_km ? parseFloat(zone.max_distance_km).toFixed(2) + " km" : "-" }}
						</td>
						<td class="db-table-body-td">
							{{ currencyFormat(zone.delivery_price) }}
						</td>
						<td class="db-table-body-td">
							<span :class="statusClass(zone.status)">{{ enums.statusEnumArray[zone.status] }}</span>
						</td>
						<td class="db-table-body-td">
							<div class="flex justify-start items-center gap-1.5">
								<SmModalEditComponent @click="edit(zone)" />
								<SmDeleteComponent @click="destroy(zone.id)" />
							</div>
						</td>
					</tr>
				</tbody>
				<tbody class="db-table-body" v-else>
					<tr class="db-table-body-tr">
						<td class="db-table-body-td text-center" colspan="6">
							<div class="p-4">
								<div class="max-w-[300px] mx-auto mt-2">
									<img class="w-full h-full" :src="ENV.API_URL + '/images/default/not-found.png'" alt="Not Found" />
								</div>
								<span class="d-block mt-3 text-lg">{{ $t("message.no_data_available") }}</span>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-6" v-if="zones.length > 0">
			<PaginationSMBox :pagination="pagination" :method="list" />
			<div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
				<PaginationTextComponent :props="{ page: paginationPage }" />
				<PaginationBox :pagination="pagination" :method="list" />
			</div>
		</div>
	</div>
</template>
<script>
import LoadingComponent from "../../components/LoadingComponent";
import TableLimitComponent from "../../components/TableLimitComponent";
import PaginationSMBox from "../../components/pagination/PaginationSMBox";
import PaginationBox from "../../components/pagination/PaginationBox";
import PaginationTextComponent from "../../components/pagination/PaginationTextComponent";
import SmDeleteComponent from "../../components/buttons/SmDeleteComponent";
import SmModalEditComponent from "../../components/buttons/SmModalEditComponent";
import DeliveryZoneCreateComponent from "./DeliveryZoneCreateComponent";
import appService from "../../../../services/appService";
import statusEnum from "../../../../enums/modules/statusEnum";
import ENV from "../../../../config/env";

export default {
	name: "DeliveryZoneListComponent",
	components: {
		LoadingComponent,
		TableLimitComponent,
		PaginationSMBox,
		PaginationBox,
		PaginationTextComponent,
		SmDeleteComponent,
		SmModalEditComponent,
		DeliveryZoneCreateComponent,
	},
	data() {
		return {
			loading: { isActive: false },
			ENV: ENV,
			enums: {
				statusEnum: statusEnum,
				statusEnumArray: {
					[statusEnum.ACTIVE]: this.$t("label.active"),
					[statusEnum.INACTIVE]: this.$t("label.inactive"),
				},
			},
			props: {
				form: {
					branch_id: null,
					name: "",
					max_distance_km: "",
					delivery_price: "",
					sort_order: 0,
					status: statusEnum.ACTIVE,
				},
				search: {
					paginate: 1,
					page: 1,
					per_page: 10,
					order_column: "id",
					order_type: "desc",
				},
			},
		};
	},
	mounted() {
		this.list();
	},
	computed: {
		zones: function () {
			return this.$store.getters["deliveryZone/lists"];
		},
		pagination: function () {
			return this.$store.getters["deliveryZone/pagination"];
		},
		paginationPage: function () {
			return this.$store.getters["deliveryZone/page"];
		},
		setting: function () {
			return this.$store.getters["frontendSetting/lists"];
		},
	},
	methods: {
		statusClass: function (status) {
			return appService.statusClass(status);
		},
		currencyFormat: function (amount) {
			return appService.currencyFormat(
				amount,
				this.setting.site_digit_after_decimal_point,
				this.setting.site_default_currency_symbol,
				this.setting.site_currency_position
			);
		},
		list: function (page = 1) {
			this.loading.isActive = true;
			this.props.search.page = page;
			this.$store
				.dispatch("deliveryZone/lists", this.props.search)
				.then(() => {
					this.loading.isActive = false;
				})
				.catch(() => {
					this.loading.isActive = false;
				});
		},
		edit: function (zone) {
			appService.modalShow("#db-modal");
			this.$store.dispatch("deliveryZone/edit", zone.id);
			// Map status values: 1 (active) -> 5 (ACTIVE), 0 (inactive) -> 10 (INACTIVE)
			let mappedStatus = zone.status;
			if (zone.status === 1) {
				mappedStatus = statusEnum.ACTIVE;
			} else if (zone.status === 0) {
				mappedStatus = statusEnum.INACTIVE;
			} else if (zone.status === 5) {
				mappedStatus = statusEnum.ACTIVE;
			} else if (zone.status === 10) {
				mappedStatus = statusEnum.INACTIVE;
			}
			this.props.form = {
				branch_id: zone.branch_id,
				name: zone.name || zone.zone_name || "",
				max_distance_km: zone.max_distance_km || "",
				delivery_price: zone.delivery_price || "",
				sort_order: zone.sort_order || 0,
				status: mappedStatus,
			};
		},
		destroy: function (id) {
			appService
				.destroyConfirmation()
				.then(() => {
					this.loading.isActive = true;
					this.$store
						.dispatch("deliveryZone/destroy", { id: id, search: this.props.search })
						.then(() => {
							this.loading.isActive = false;
						})
						.catch((err) => {
							this.loading.isActive = false;
						});
				})
				.catch(() => {
					this.loading.isActive = false;
				});
		},
	},
};
</script>


