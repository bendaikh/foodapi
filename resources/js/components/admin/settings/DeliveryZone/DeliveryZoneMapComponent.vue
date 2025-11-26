<template>
    <div class="delivery-zone-map-container">
        <div class="mb-4">
            <label class="db-field-title">{{ $t("label.business_address") || "Business Address" }}</label>
            <input type="text" class="db-field-control bg-gray-50" :value="branchAddress" readonly />
        </div>
        
        <div class="relative">
            <!-- Clear button -->
            <button 
                v-if="radiusKm > 0" 
                @click="clearRadius" 
                type="button"
                class="absolute top-2 left-2 z-10 flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-white rounded shadow hover:bg-gray-50"
            >
                <i class="fa-solid fa-eraser"></i>
                {{ $t("button.clear") || "Clear" }}
            </button>
            
            <!-- Map container -->
            <div :id="mapId" class="w-full h-[400px] rounded-xl border border-gray-200"></div>
            
            <!-- Radius info popup -->
            <div 
                v-if="radiusKm > 0" 
                class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-lg shadow-lg p-4 z-10 pointer-events-none"
            >
                <button 
                    @click="closeRadiusInfo" 
                    type="button" 
                    class="absolute -top-2 -right-2 w-6 h-6 flex items-center justify-center bg-gray-100 rounded-full hover:bg-gray-200 pointer-events-auto"
                >
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
                <div class="text-center">
                    <div class="text-sm font-semibold text-gray-700 mb-1">{{ $t("label.radius") || "Radius" }}:</div>
                    <div class="text-lg font-bold text-primary">{{ radiusKm.toFixed(2) }}km</div>
                    <div class="text-sm text-gray-500">{{ radiusMiles.toFixed(2) }}mi</div>
                </div>
            </div>
        </div>
        
        <!-- Radius input slider -->
        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium text-gray-700">{{ $t("label.delivery_radius") || "Delivery Radius" }}</label>
                <div class="flex items-center gap-2">
                    <input 
                        type="number" 
                        v-model.number="radiusKm" 
                        @input="onRadiusInput"
                        min="0.1" 
                        max="50" 
                        step="0.1"
                        class="w-20 px-2 py-1 text-sm border rounded text-center"
                    />
                    <span class="text-sm text-gray-600">km</span>
                </div>
            </div>
            <input 
                type="range" 
                v-model.number="radiusKm" 
                @input="onRadiusInput"
                min="0.1" 
                max="50" 
                step="0.1"
                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary"
            />
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>0.1 km</span>
                <span>25 km</span>
                <span>50 km</span>
            </div>
        </div>
    </div>
</template>

<script>
import { loadGoogleMaps } from "../../../../utils/googleMapsLoader";

export default {
    name: "DeliveryZoneMapComponent",
    props: {
        branchId: {
            type: [Number, String],
            default: null
        },
        initialRadius: {
            type: Number,
            default: 1.5
        }
    },
    data() {
        return {
            mapId: 'delivery-zone-map-' + Math.random().toString(36).substr(2, 9),
            map: null,
            circle: null,
            marker: null,
            radiusKm: this.initialRadius || 1.5,
            branchLocation: { lat: 0, lng: 0 },
            branchAddress: "",
            showRadiusInfo: true,
            google: null
        };
    },
    computed: {
        radiusMiles() {
            return this.radiusKm * 0.621371;
        },
        radiusMeters() {
            return this.radiusKm * 1000;
        },
        selectedBranch() {
            const branches = this.$store.getters["branch/lists"] || [];
            return branches.find(b => b.id === this.branchId);
        }
    },
    watch: {
        branchId: {
            immediate: true,
            handler(newVal) {
                if (newVal) {
                    this.loadBranchAndInitMap();
                }
            }
        },
        initialRadius: {
            immediate: true,
            handler(newVal) {
                if (newVal && newVal !== this.radiusKm) {
                    this.radiusKm = newVal;
                    this.updateCircle();
                }
            }
        }
    },
    methods: {
        async loadBranchAndInitMap() {
            if (!this.branchId) return;
            
            const branch = this.selectedBranch;
            if (branch && branch.latitude && branch.longitude) {
                this.branchLocation = {
                    lat: parseFloat(branch.latitude),
                    lng: parseFloat(branch.longitude)
                };
                this.branchAddress = branch.address || branch.name || "";
                await this.initMap();
            }
        },
        
        async initMap() {
            try {
                this.google = await loadGoogleMaps();
                
                // Wait for DOM element to be ready
                await this.$nextTick();
                
                const mapElement = document.getElementById(this.mapId);
                if (!mapElement) {
                    console.error("Map element not found");
                    return;
                }
                
                // Calculate appropriate zoom level based on radius
                const zoom = this.getZoomLevel(this.radiusKm);
                
                this.map = new this.google.maps.Map(mapElement, {
                    center: this.branchLocation,
                    zoom: zoom,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: true
                });
                
                // Add branch marker
                this.marker = new this.google.maps.Marker({
                    position: this.branchLocation,
                    map: this.map,
                    title: this.branchAddress,
                    icon: {
                        url: "https://maps.google.com/mapfiles/ms/icons/red-dot.png"
                    }
                });
                
                // Add delivery zone circle
                this.circle = new this.google.maps.Circle({
                    strokeColor: "#22C55E",
                    strokeOpacity: 0.9,
                    strokeWeight: 3,
                    fillColor: "#22C55E",
                    fillOpacity: 0.15,
                    map: this.map,
                    center: this.branchLocation,
                    radius: this.radiusMeters,
                    editable: true,
                    draggable: false
                });
                
                // Listen for circle radius change (when user drags the edge)
                this.google.maps.event.addListener(this.circle, 'radius_changed', () => {
                    const newRadiusKm = this.circle.getRadius() / 1000;
                    this.radiusKm = Math.round(newRadiusKm * 100) / 100;
                    this.emitRadiusChange();
                    this.adjustMapZoom();
                });
                
            } catch (error) {
                console.error("Error initializing map:", error);
            }
        },
        
        getZoomLevel(radiusKm) {
            // Approximate zoom levels for different radii
            if (radiusKm <= 0.5) return 16;
            if (radiusKm <= 1) return 15;
            if (radiusKm <= 2) return 14;
            if (radiusKm <= 5) return 13;
            if (radiusKm <= 10) return 12;
            if (radiusKm <= 20) return 11;
            if (radiusKm <= 40) return 10;
            return 9;
        },
        
        onRadiusInput() {
            this.updateCircle();
            this.emitRadiusChange();
        },
        
        updateCircle() {
            if (this.circle) {
                this.circle.setRadius(this.radiusMeters);
                this.adjustMapZoom();
            }
        },
        
        adjustMapZoom() {
            if (this.map && this.circle) {
                const bounds = this.circle.getBounds();
                if (bounds) {
                    this.map.fitBounds(bounds);
                }
            }
        },
        
        clearRadius() {
            this.radiusKm = 0.1;
            this.updateCircle();
            this.emitRadiusChange();
        },
        
        closeRadiusInfo() {
            this.showRadiusInfo = false;
        },
        
        emitRadiusChange() {
            this.$emit('radiusChange', {
                radiusKm: this.radiusKm,
                radiusMiles: this.radiusMiles,
                radiusMeters: this.radiusMeters
            });
        }
    }
};
</script>

<style scoped>
.delivery-zone-map-container {
    width: 100%;
}

input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--primary-color, #FF6B35);
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

input[type="range"]::-moz-range-thumb {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--primary-color, #FF6B35);
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    border: none;
}
</style>

