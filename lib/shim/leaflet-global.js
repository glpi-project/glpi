import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
// spin.js dependency
// Spinner object have to be accessible in window context
import { Spinner } from '../shim/spin-shim.js';

window.L = L;
window.L.Icon.Default.imagePath = new URL('../lib/leaflet/dist/images/', import.meta.url).href;
window.Spinner = Spinner;

export default L;
