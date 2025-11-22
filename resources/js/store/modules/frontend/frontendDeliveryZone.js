import axios from "axios";

export const frontendDeliveryZone = {
	namespaced: true,
	state: {
		lists: [],
	},
	getters: {
		lists: function (state) {
			return state.lists;
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
	},
	mutations: {
		lists: function (state, payload) {
			state.lists = payload;
		},
	},
};


