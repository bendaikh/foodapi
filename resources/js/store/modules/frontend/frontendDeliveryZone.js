import axios from "axios";

export const frontendDeliveryZone = {
	namespaced: true,
	state: {
		lists: [],
		detectedZone: null,
	},
	getters: {
		lists: function (state) {
			return state.lists;
		},
		detectedZone: function (state) {
			return state.detectedZone;
		},
	},
	actions: {
		lists: function (context) {
			return new Promise((resolve, reject) => {
				axios
					.get("frontend/delivery-zone")
					.then((res) => {
						context.commit("lists", res.data.data);
						resolve(res);
					})
					.catch((err) => {
						reject(err);
					});
			});
		},
		detectZone: function (context, payload) {
			return new Promise((resolve, reject) => {
				axios
					.post(`frontend/delivery-zone/branch/${payload.branch_id}/detect`, {
						latitude: payload.latitude,
						longitude: payload.longitude,
					})
					.then((res) => {
						context.commit("detectedZone", res.data.data);
						resolve(res);
					})
					.catch((err) => {
						context.commit("detectedZone", null);
						reject(err);
					});
			});
		},
	},
	mutations: {
		lists: function (state, payload) {
			state.lists = payload;
		},
		detectedZone: function (state, payload) {
			state.detectedZone = payload;
		},
	},
};


