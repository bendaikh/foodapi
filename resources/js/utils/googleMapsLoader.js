import { Loader } from "@googlemaps/js-api-loader";
import ENV from '../config/env';

// Create a single loader instance
const loader = new Loader({
    apiKey: ENV.GOOGLE_MAP_KEY,
    version: "weekly",
    libraries: ["places", "geometry", "drawing"]
});

// Load Google Maps - returns a promise that resolves to google object
export async function loadGoogleMaps() {
    return await loader.load();
}

