import axios from "axios";
import appService from "../../services/appService";

export const deliveryZone = {
	namespaced: true,
	state: {
		lists: [],
		page: {},
		pagination: [],
		temp: {
			temp_id: null,
			isEditing: false,
		},
	},
	getters: {
		lists: function (state) {
			return state.lists;
		},
		pagination: function (state) {
			return state.pagination;
		},
		page: function (state) {
			return state.page;
		},
		temp: function (state) {
			return state.temp;
		},
	},
	actions: {
		lists: function (context, payload) {
			return new Promise((resolve, reject) => {
				let url = "admin/setting/delivery-zone";
				if (payload) {
					url = url + appService.requestHandler(payload);
				}
				axios
					.get(url)
					.then((res) => {
						if (
							typeof payload?.vuex === "undefined" ||
							payload.vuex === true
						) {
							// Map status values: 1 (active) -> 5 (ACTIVE), 0 (inactive) -> 10 (INACTIVE)
							const mappedData = res.data.data.map(zone => ({
								...zone,
								status: zone.status === 1 ? 5 : zone.status === 0 ? 10 : zone.status
							}));
							context.commit("lists", mappedData);
							context.commit("page", res.data.meta);
							context.commit("pagination", res.data);
						}
						resolve(res);
					})
					.catch((err) => {
						reject(err);
					});
			});
		},
		save: function (context, payload) {
			return new Promise((resolve, reject) => {
				let method = axios.post;
				let url = "/admin/setting/delivery-zone";
				if (this.state["deliveryZone"].temp.isEditing) {
					method = axios.put;
					url = `/admin/setting/delivery-zone/${this.state["deliveryZone"].temp.temp_id}`;
				}
				
				const formData = { ...payload.form };
				
				// Ensure numeric fields are properly formatted
				if (formData.branch_id) {
					formData.branch_id = parseInt(formData.branch_id, 10);
				}
				if (formData.max_distance_km) {
					formData.max_distance_km = parseFloat(formData.max_distance_km);
				}
				if (formData.delivery_price) {
					formData.delivery_price = parseFloat(formData.delivery_price);
				}
				if (formData.sort_order !== undefined && formData.sort_order !== null) {
					formData.sort_order = parseInt(formData.sort_order, 10) || 0;
				} else {
					formData.sort_order = 0;
				}
				
				// Map status: frontend uses 5/10, backend expects numeric (5/10 or 0-24)
				const originalStatus = formData.status;
				if (originalStatus == 5 || originalStatus === '5') {
					formData.status = 5;
				} else if (originalStatus == 10 || originalStatus === '10') {
					formData.status = 10;
				} else {
					formData.status = parseInt(originalStatus, 10) || 5;
				}
				
				method(url, formData)
					.then((res) => {
						context
							.dispatch("lists", payload.search)
							.then()
							.catch();
						context.commit("reset");
						resolve(res);
					})
					.catch((err) => {
						reject(err);
					});
			});
		},
		edit: function (context, payload) {
			context.commit("temp", payload);
		},
		destroy: function (context, payload) {
			return new Promise((resolve, reject) => {
				axios
					.delete(`admin/setting/delivery-zone/${payload.id}`)
					.then((res) => {
						context
							.dispatch("lists", payload.search)
							.then()
							.catch();
						resolve(res);
					})
					.catch((err) => {
						reject(err);
					});
			});
		},
		reset: function (context) {
			context.commit("reset");
		},
	},
	mutations: {
		lists: function (state, payload) {
			state.lists = payload;
		},
		pagination: function (state, payload) {
			state.pagination = payload;
		},
		page: function (state, payload) {
			if (typeof payload !== "undefined" && payload !== null) {
				state.page = {
					from: payload.from,
					to: payload.to,
					total: payload.total,
				};
			}
		},
		temp: function (state, payload) {
			state.temp.temp_id = payload;
			state.temp.isEditing = true;
		},
		reset: function (state) {
			state.temp.temp_id = null;
			state.temp.isEditing = false;
		},
	},
};


