/* ============================================================
   Defenza – Main JavaScript
   Frontend Demo Mode — enhanced animations & demo behaviors
   ============================================================ */

'use strict';

let liveLocationInterval = null;

// ── Geolocation state for safety features ─────────────────────────
window.DefenzaGeolocation = {
  userLat: null,
  userLng: null,
  lastError: null
};

window.DefenzaLiveLocationService = {
  watchId: null,
  lastPosition: null,
  lastPositionAt: 0,
  lastSyncedAt: 0,
  pendingBestPositionRequest: null,
  map: null,
  marker: null,
  circle: null,
  locationPopup: null,
  mapLoaded: false,
  pendingLocationUpdate: null,
  userMovedMap: false,
  userLocationSourceId: 'defenza-user-location',
  userLocationLayerId: 'defenza-user-location-point',
  userAccuracyLayerId: 'defenza-user-accuracy-fill',
  mapContainerId: 'liveLocationMap',
  ui: null,
  mapCenter: null,
  mapZoom: null,
  lastMapUpdateAt: 0,
  debugStats: {
    activeWatchCount: 0,
    mapInitCount: 0,
    markerUpdates: 0,
    circleUpdates: 0,
    setViewCalls: 0,
    flyToCalls: 0,
    panToCalls: 0,
    lastPositionChangeMeters: 0,
    lastCenteringAt: 0
  },

  setUi(ui = {}) {
    this.ui = { ...(this.ui || {}), ...ui };
  },

  getDebugState() {
    return {
      watchId: this.watchId,
      activeWatchCount: this.debugStats.activeWatchCount,
      mapInitCount: this.debugStats.mapInitCount,
      markerUpdates: this.debugStats.markerUpdates,
      circleUpdates: this.debugStats.circleUpdates,
      setViewCalls: this.debugStats.setViewCalls,
      flyToCalls: this.debugStats.flyToCalls,
      panToCalls: this.debugStats.panToCalls,
      mapInstanceCount: this.map ? 1 : 0,
      markerCount: this.marker ? 1 : 0,
      circleCount: this.circle ? 1 : 0,
      mapCenter: this.mapCenter,
      mapZoom: this.mapZoom,
      lastPositionChangeMeters: this.debugStats.lastPositionChangeMeters,
      lastCenteringAt: this.debugStats.lastCenteringAt
    };
  },

  attachMapDebugHooks() {
    if (!this.map || this.map.__defenzaDebugHooked) return;
    this.map.__defenzaDebugHooked = true;

    const wrapMethod = (methodName, counterKey) => {
      const originalMethod = this.map[methodName];
      if (typeof originalMethod !== 'function') return;
      this.map[methodName] = (...args) => {
        this.debugStats[counterKey] += 1;
        return originalMethod.apply(this.map, args);
      };
    };

    wrapMethod('setView', 'setViewCalls');
    wrapMethod('flyTo', 'flyToCalls');
    wrapMethod('panTo', 'panToCalls');
  },

  updateUi(lat, lon, acc, address = 'Current GPS Coordinates', status = 'active') {
    const safeLat = Number(lat);
    const safeLon = Number(lon);
    const safeAcc = Number(acc);

    if (Number.isFinite(safeLat) && Number.isFinite(safeLon)) {
      if (typeof updateLiveLocationUI === 'function') {
        updateLiveLocationUI(safeLat, safeLon, Number.isFinite(safeAcc) && safeAcc > 0 ? safeAcc : 0, address || 'Current GPS Coordinates');
      } else {
        const label = document.getElementById('locationLabel');
        if (label) label.textContent = `Lat ${safeLat.toFixed(4)}, Lon ${safeLon.toFixed(4)}`;
      }
    }

    if (typeof setLiveLocationStatus === 'function') {
      setLiveLocationStatus(status !== 'paused');
    }
  },

  getDistanceMeters(lat1, lon1, lat2, lon2) {
    const toRad = (value) => (value * Math.PI) / 180;
    const earthRadius = 6371000;
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
    return 2 * earthRadius * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  },

  toRadians(value) {
    return (value * Math.PI) / 180;
  },

  toDegrees(value) {
    return (value * 180) / Math.PI;
  },

  createAccuracyCircleCoordinates(lat, lon, radiusMeters, points = 64) {
    const coords = [];
    const angularDistance = radiusMeters / 6371000;
    const latRad = this.toRadians(lat);
    const lonRad = this.toRadians(lon);

    for (let i = 0; i <= points; i += 1) {
      const bearing = 2 * Math.PI * (i / points);
      const lat2 = Math.asin(
        Math.sin(latRad) * Math.cos(angularDistance) +
          Math.cos(latRad) * Math.sin(angularDistance) * Math.cos(bearing)
      );
      const lon2 = lonRad +
        Math.atan2(
          Math.sin(bearing) * Math.sin(angularDistance) * Math.cos(latRad),
          Math.cos(angularDistance) - Math.sin(latRad) * Math.sin(lat2)
        );
      coords.push([this.toDegrees(lon2), this.toDegrees(lat2)]);
    }

    return coords;
  },

  getLocationGeoJson(lat, lon, accuracy) {
    return {
      type: 'FeatureCollection',
      features: [
        {
          type: 'Feature',
          geometry: {
            type: 'Polygon',
            coordinates: [this.createAccuracyCircleCoordinates(lat, lon, accuracy)]
          },
          properties: {
            featureType: 'accuracy'
          }
        },
        {
          type: 'Feature',
          geometry: {
            type: 'Point',
            coordinates: [Number(lon), Number(lat)]
          },
          properties: {
            featureType: 'point'
          }
        }
      ]
    };
  },

  updateLocationSource(lat, lon, accuracy) {
    if (!this.map || !this.map.getSource || !this.map.getSource(this.userLocationSourceId)) {
      this.pendingLocationUpdate = { lat, lon, accuracy };
      return;
    }
    const source = this.map.getSource(this.userLocationSourceId);
    if (source && typeof source.setData === 'function') {
      source.setData(this.getLocationGeoJson(lat, lon, accuracy));
    }
  },

  createPopupContent(lat, lon, accuracy, timestamp) {
    const formattedTime = timestamp ? new Date(timestamp).toLocaleTimeString() : new Date().toLocaleTimeString();
    return `
      <div style="font-family: var(--font); max-width: 220px;">
        <div style="font-size:0.95rem;font-weight:800;margin-bottom:0.35rem;">Current Location</div>
        <div style="font-size:0.82rem;color:#475569;line-height:1.4;">
          <strong>Latitude:</strong> ${lat.toFixed(6)}<br />
          <strong>Longitude:</strong> ${lon.toFixed(6)}<br />
          <strong>Accuracy:</strong> ${Math.round(accuracy)} m<br />
          <strong>Last Updated:</strong> ${formattedTime}
        </div>
      </div>
    `;
  },

  attachLocationPopup() {
    if (!this.map || this.locationPopup) return;
    this.locationPopup = new maplibregl.Popup({ closeButton: true, closeOnClick: true, className: 'defenza-map-popup' });

    this.map.on('click', this.userLocationLayerId, (event) => {
      const coords = event.features?.[0]?.geometry?.coordinates;
      if (!coords || !this.lastPosition) return;
      this.locationPopup
        .setLngLat(coords)
        .setHTML(this.createPopupContent(this.lastPosition.lat, this.lastPosition.lon, this.lastPosition.acc, this.lastPositionAt))
        .addTo(this.map);
    });

    this.map.on('mouseenter', this.userLocationLayerId, () => {
      this.map.getCanvas().style.cursor = 'pointer';
    });
    this.map.on('mouseleave', this.userLocationLayerId, () => {
      this.map.getCanvas().style.cursor = '';
    });
  },

  getDefaultAccuracyThresholdMeters() {
    if (typeof window === 'undefined') {
      return 50;
    }

    const hostname = String(window.location.hostname || '').toLowerCase();
    const localHosts = ['localhost', '127.0.0.1', '::1'];

    if (localHosts.includes(hostname) || hostname.endsWith('.local') || hostname.endsWith('.test')) {
      return 150;
    }

    return 50;
  },

  getAccuracyThresholdMeters() {
    const configuredValue = typeof window !== 'undefined' && window.DefenzaConfig && Number.isFinite(Number(window.DefenzaConfig.locationAccuracyThresholdMeters))
      ? Number(window.DefenzaConfig.locationAccuracyThresholdMeters)
      : null;

    const threshold = configuredValue > 0 ? Number(configuredValue) : this.getDefaultAccuracyThresholdMeters();
    return threshold > 0 ? threshold : 50;
  },

  getGeolocationTimeoutMillis() {
    return 15000;
  },

  getGeolocationOptions() {
    return {
      enableHighAccuracy: true,
      timeout: this.getGeolocationTimeoutMillis(),
      maximumAge: 0
    };
  },

  getPositionSource(position) {
    if (!position || !position.coords) {
      return 'unknown';
    }

    const accuracy = Number(position.coords.accuracy ?? Infinity);
    const ageMs = Math.max(0, Date.now() - Number(position.timestamp ?? Date.now()));

    if (ageMs > 10000) {
      return 'cached';
    }
    if (accuracy <= 50) {
      return 'GPS';
    }
    if (accuracy <= 150) {
      return 'Wi-Fi';
    }
    if (accuracy <= 300) {
      return 'Network';
    }
    return 'Unknown';
  },

  isPositionAccurate(position) {
    const accuracy = Number(position?.coords?.accuracy ?? position?.acc ?? position?.accuracy ?? Infinity);
    return Number.isFinite(accuracy) && accuracy > 0 && accuracy <= this.getAccuracyThresholdMeters();
  },

  updateAcquisitionStatus(position) {
    const accuracy = Number(position?.coords?.accuracy ?? position?.acc ?? position?.accuracy ?? 0);
    const statusEl = this.ui?.statusTextId ? document.getElementById(this.ui.statusTextId) : document.getElementById('liveStatusText');
    const labelEl = this.ui?.locationLabelId ? document.getElementById(this.ui.locationLabelId) : document.getElementById('locationLabel');

    if (statusEl) {
      statusEl.textContent = `Acquiring accurate GPS location… Accuracy: ${accuracy.toFixed(0)} m`;
      statusEl.style.color = '#f59e0b';
    }
    if (labelEl) {
      labelEl.textContent = `Accuracy: ${accuracy.toFixed(0)} m`;
    }
  },

  clearAcquisitionStatus() {
    const statusEl = this.ui?.statusTextId ? document.getElementById(this.ui.statusTextId) : document.getElementById('liveStatusText');
    if (statusEl) {
      statusEl.textContent = 'Active — updating every 10s';
      statusEl.style.color = '#16a34a';
    }
  },

  ensureWatchPosition() {
    if (!navigator.geolocation) {
      return null;
    }

    if (this.watchId !== null) {
      return this.watchId;
    }

    const options = this.getGeolocationOptions();
    console.debug('[Defenza Debug] ensureWatchPosition options', options);

    this.watchId = navigator.geolocation.watchPosition(
      (position) => this.handleWatchPositionUpdate(position),
      (error) => this.handleWatchPositionError(error),
      options
    );

    this.debugStats.activeWatchCount += 1;
    return this.watchId;
  },

  resolvePendingBestPosition() {
    if (!this.pendingBestPositionRequest) {
      return;
    }

    const pending = this.pendingBestPositionRequest;
    window.clearTimeout(pending.timeoutId);
    const selected = pending.bestPosition || pending.lastPosition || null;
    this.pendingBestPositionRequest = null;
    this.clearAcquisitionStatus();
    pending.resolve(selected);
  },

  handleWatchPositionUpdate(position) {
    const accuracy = Number(position?.coords?.accuracy ?? 0);
    const source = this.getPositionSource(position);
    const isAccurate = this.isPositionAccurate(position);

    console.debug('[Defenza Debug] watchPosition position object', {
      latitude: position?.coords?.latitude,
      longitude: position?.coords?.longitude,
      accuracy,
      altitude: position?.coords?.altitude,
      heading: position?.coords?.heading,
      speed: position?.coords?.speed,
      timestamp: position?.timestamp,
      source
    });

    if (this.pendingBestPositionRequest) {
      const pending = this.pendingBestPositionRequest;
      pending.lastPosition = position;
      if (!pending.bestPosition || accuracy < Number(pending.bestPosition?.coords?.accuracy ?? Infinity)) {
        pending.bestPosition = position;
      }

      if (isAccurate) {
        this.resolvePendingBestPosition();
      } else {
        this.updateAcquisitionStatus(position);
      }
      return;
    }

    if (!isAccurate) {
      this.updateAcquisitionStatus(position);
      return;
    }

    const location = this.persistPosition(position, 'Current GPS Coordinates', 'active');
    if (location) {
      this.initMap(location.lat, location.lon, location.acc);
      this.updateUi(location.lat, location.lon, location.acc, location.address, location.status || 'active');
      this.syncLocationToServer(location, { skipIfRecent: true }).catch(() => {});
    }
  },

  handleWatchPositionError(error) {
    const watchOptions = this.getGeolocationOptions();
    console.debug('[Defenza Debug] watchPosition error', {
      code: error?.code,
      message: error?.message,
      options: watchOptions
    });

    if (this.pendingBestPositionRequest) {
      this.resolvePendingBestPosition();
    }

    if (error && error.code === error.PERMISSION_DENIED) {
      if (typeof showLocationPermissionBanner === 'function') {
        showLocationPermissionBanner(true);
      }
      if (this.watchId !== null) {
        navigator.geolocation.clearWatch(this.watchId);
        this.watchId = null;
      }
    }
  },

  async acquireBestPosition() {
    if (!navigator.geolocation) {
      return null;
    }

    if (this.pendingBestPositionRequest) {
      return this.pendingBestPositionRequest.promise;
    }

    this.ensureWatchPosition();

    const timeoutMs = this.getGeolocationTimeoutMillis();
    let resolvePromise = null;

    const pendingRequest = {
      promise: null,
      resolve: null,
      lastPosition: this.lastPosition ? {
        coords: {
          latitude: this.lastPosition.lat,
          longitude: this.lastPosition.lon,
          accuracy: this.lastPosition.acc
        },
        timestamp: this.lastPosition.timestamp
      } : null,
      bestPosition: this.lastPosition ? {
        coords: {
          latitude: this.lastPosition.lat,
          longitude: this.lastPosition.lon,
          accuracy: this.lastPosition.acc
        },
        timestamp: this.lastPosition.timestamp
      } : null,
      timeoutId: null
    };

    const promise = new Promise((resolve) => {
      resolvePromise = resolve;
    });

    pendingRequest.promise = promise;
    pendingRequest.resolve = resolvePromise;
    this.pendingBestPositionRequest = pendingRequest;
    pendingRequest.timeoutId = window.setTimeout(() => {
      this.resolvePendingBestPosition();
    }, timeoutMs);

    if (this.lastPosition) {
      const lastPseudoPosition = pendingRequest.lastPosition;
      this.updateAcquisitionStatus(lastPseudoPosition);
    }

    return promise;
  },

  initMap(lat, lon, accuracy) {
    const container = document.getElementById(this.mapContainerId);
    if (!container || typeof maplibregl === 'undefined') return;

    const safeLat = Number(lat);
    const safeLon = Number(lon);
    const safeAccuracy = Math.max(Number(accuracy) || 20, 20);

    if (!this.map) {
      this.mapLoaded = false;
      this.map = new maplibregl.Map({
        container: this.mapContainerId,
        style: 'https://demotiles.maplibre.org/style.json',
        center: [safeLon, safeLat],
        zoom: 16,
        attributionControl: false
      });

      this.map.addControl(new maplibregl.NavigationControl({ visualizePitch: false }), 'top-right');
      this.map.addControl(new maplibregl.FullscreenControl(), 'top-right');
      this.map.addControl(new maplibregl.ScaleControl({ maxWidth: 120, unit: 'metric' }), 'bottom-left');

      this.map.on('load', () => {
        this.mapLoaded = true;
        if (!this.map.getSource(this.userLocationSourceId)) {
          this.map.addSource(this.userLocationSourceId, {
            type: 'geojson',
            data: this.getLocationGeoJson(safeLat, safeLon, safeAccuracy)
          });

          this.map.addLayer({
            id: this.userAccuracyLayerId,
            type: 'fill',
            source: this.userLocationSourceId,
            filter: ['==', ['get', 'featureType'], 'accuracy'],
            paint: {
              'fill-color': '#2563eb',
              'fill-opacity': 0.08,
              'fill-outline-color': 'rgba(37,99,235,0.35)'
            }
          });

          this.map.addLayer({
            id: `${this.userAccuracyLayerId}-line`,
            type: 'line',
            source: this.userLocationSourceId,
            filter: ['==', ['get', 'featureType'], 'accuracy'],
            paint: {
              'line-color': '#2563eb',
              'line-width': 2,
              'line-dasharray': [2, 2]
            }
          });

          this.map.addLayer({
            id: this.userLocationLayerId,
            type: 'circle',
            source: this.userLocationSourceId,
            filter: ['==', ['get', 'featureType'], 'point'],
            paint: {
              'circle-radius': 10,
              'circle-color': '#2563eb',
              'circle-stroke-color': '#ffffff',
              'circle-stroke-width': 4,
              'circle-blur': 0.35
            }
          });

          this.attachLocationPopup();
        }

        if (this.pendingLocationUpdate) {
          const pending = this.pendingLocationUpdate;
          this.pendingLocationUpdate = null;
          this.updateLocationSource(pending.lat, pending.lon, pending.accuracy);
        }
      });

      this.map.on('moveend', () => {
        this.mapZoom = this.map.getZoom();
      });

      this.map.on('dragstart', () => {
        this.userMovedMap = true;
      });

      this.debugStats.mapInitCount += 1;
      this.mapCenter = [safeLat, safeLon];
      this.mapZoom = 16;
      this.lastMapUpdateAt = Date.now();
      this.updateLocationSource(safeLat, safeLon, safeAccuracy);
      return;
    }

    if (!this.mapLoaded && this.map.getSource && this.map.getSource(this.userLocationSourceId)) {
      this.mapLoaded = true;
    }

    const previousPosition = this.lastPosition;
    const now = Date.now();
    const samePositionWindow = previousPosition && (now - (this.lastMapUpdateAt || 0)) < 10000;
    let distanceFromPrevious = 0;
    if (previousPosition) {
      distanceFromPrevious = this.getDistanceMeters(previousPosition.lat, previousPosition.lon, safeLat, safeLon);
      this.debugStats.lastPositionChangeMeters = distanceFromPrevious;
    }

    if (!samePositionWindow || distanceFromPrevious > 12) {
      this.debugStats.markerUpdates += 1;
      this.debugStats.circleUpdates += 1;
      this.updateLocationSource(safeLat, safeLon, safeAccuracy);
    }

    const currentCenter = this.map.getCenter();
    const currentLat = Number(currentCenter?.lat ?? 0);
    const currentLon = Number(currentCenter?.lng ?? 0);
    const distanceFromCenter = this.getDistanceMeters(currentLat, currentLon, safeLat, safeLon);
    const shouldRecenter = !this.mapCenter || !this.mapZoom || (distanceFromCenter > 80 && (!samePositionWindow || distanceFromPrevious > 25)) || this.map.getZoom() < 14;

    if (shouldRecenter && !this.userMovedMap) {
      this.debugStats.lastCenteringAt = Date.now();
      this.map.easeTo({ center: [safeLon, safeLat], duration: 800 });
    }

    this.mapCenter = [safeLat, safeLon];
    this.mapZoom = this.map.getZoom();
    this.lastMapUpdateAt = now;
  },

  persistPosition(position, address = 'Current GPS Coordinates', status = 'active') {
    const lat = Number(position?.coords?.latitude ?? position?.lat ?? position?.latitude);
    const lon = Number(position?.coords?.longitude ?? position?.lon ?? position?.longitude);
    const acc = Number(position?.coords?.accuracy ?? position?.acc ?? position?.accuracy ?? 0);

    console.debug('[Defenza Debug] Browser geolocation success', { lat, lon, acc, address, status, source: position?.coords ? 'navigator.geolocation' : 'fallback' });

    if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
      return null;
    }

    this.lastPosition = {
      lat,
      lon,
      acc,
      address: address || 'Current GPS Coordinates',
      status,
      timestamp: Date.now()
    };
    this.lastPositionAt = this.lastPosition.timestamp;

    window.DefenzaGeolocation.userLat = lat;
    window.DefenzaGeolocation.userLng = lon;
    window.userLat = lat;
    window.userLng = lon;
    window.DefenzaGeolocation.lastError = null;

    if (window.localStorage) {
      try {
        window.localStorage.setItem('defenza_last_known_location', JSON.stringify(this.lastPosition));
      } catch (_) {}
    }

    return this.lastPosition;
  },

  async syncLocationToServer(location, options = {}) {
    if (!location) return null;

    const now = Date.now();
    const skipRecent = options.skipIfRecent !== false;
    if (skipRecent && this.lastSyncedAt && (now - this.lastSyncedAt) < 10000 && this.lastPosition && Math.abs(this.lastPosition.lat - location.lat) < 1e-5 && Math.abs(this.lastPosition.lon - location.lon) < 1e-5) {
      return this.lastPosition;
    }

    try {
      const apiUrl = typeof getLiveLocationApiUrl === 'function' ? getLiveLocationApiUrl() : '/api/live-location.php';
      console.debug('[Defenza Debug] Sending live location to server', { apiUrl, location });
      const params = new URLSearchParams();
      params.append('latitude', location.lat);
      params.append('longitude', location.lon);
      params.append('accuracy', location.acc ?? 0);
      params.append('address', location.address || 'Current GPS Coordinates');
      params.append('status', location.status || 'active');
      params.append('lat', location.lat);
      params.append('lon', location.lon);
      params.append('acc', location.acc ?? 0);

      const response = await fetch(apiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: params.toString()
      });
      const data = await response.json();
      console.debug('[Defenza Debug] Live location server response', { data });
      if (!data?.success) {
        throw new Error(data?.message || 'Unable to save live location.');
      }
      this.lastSyncedAt = Date.now();
      return data.location || location;
    } catch (_) {
      return null;
    }
  },

  async refreshLocation({ force = false, saveToDb = true, address = 'Current GPS Coordinates', showToast: showToastMessage = false } = {}) {
    const geoOptions = this.getGeolocationOptions();
    console.debug('[Defenza Debug] getCurrentPosition options', geoOptions);

    if (!navigator.geolocation) {
      if (showToastMessage && typeof window.showToast === 'function') {
        window.showToast('Geolocation is not supported in this browser.', 'error');
      }
      return null;
    }

    const now = Date.now();
    if (!force && this.lastPosition && (now - this.lastPositionAt) < 30000 && this.isPositionAccurate(this.lastPosition)) {
      this.updateUi(this.lastPosition.lat, this.lastPosition.lon, this.lastPosition.acc, this.lastPosition.address, this.lastPosition.status || 'active');
      if (saveToDb) {
        await this.syncLocationToServer(this.lastPosition, { skipIfRecent: true });
      }
      return this.lastPosition;
    }

    return new Promise((resolve) => {
      this.acquireBestPosition().then(async (position) => {
        if (!position) {
          if (this.lastPosition) {
            this.updateUi(this.lastPosition.lat, this.lastPosition.lon, this.lastPosition.acc, this.lastPosition.address, this.lastPosition.status || 'active');
            if (saveToDb) {
              await this.syncLocationToServer(this.lastPosition, { skipIfRecent: true });
            }
            resolve(this.lastPosition);
            return;
          }
          if (showToastMessage && typeof window.showToast === 'function') {
            window.showToast('Unable to retrieve an accurate location. Please try again in an open area.', 'warning');
          }
          resolve(null);
          return;
        }

        const accuracy = Number(position?.coords?.accuracy ?? 0);
        console.debug('[Defenza Debug] Best available position selected', {
          latitude: position?.coords?.latitude,
          longitude: position?.coords?.longitude,
          accuracy,
          threshold: this.getAccuracyThresholdMeters()
        });

        const location = this.persistPosition(position, address, 'active');
        if (location) {
          this.initMap(location.lat, location.lon, location.acc);
          this.updateUi(location.lat, location.lon, location.acc, location.address, location.status || 'active');
          if (saveToDb) {
            await this.syncLocationToServer(location, { skipIfRecent: true });
          }
        }
        resolve(location);
      });
    });
  },

  async getVerifiedLocation({ force = false, saveToDb = true, address = 'Current GPS Coordinates' } = {}) {
    const location = await this.refreshLocation({ force, saveToDb, address, showToast: false });
    if (!location) {
      return null;
    }
    return {
      lat: location.lat,
      lon: location.lon,
      acc: location.acc,
      address: location.address || address,
      latitude: location.lat,
      longitude: location.lon,
      accuracy: location.acc,
      timestamp: location.timestamp,
      status: location.status || 'active'
    };
  },

  startTracking(options = {}) {
    if (this.watchId !== null) {
      return this.watchId;
    }

    this.setUi(options.ui || {});

    if (!navigator.geolocation) {
      if (typeof showToast === 'function') {
        showToast('Geolocation is not supported in this browser.', 'error');
      }
      return null;
    }

    const watchOptions = this.getGeolocationOptions();
    console.debug('[Defenza Debug] watchPosition options', watchOptions);

    this.watchId = navigator.geolocation.watchPosition(
      (position) => {
        console.debug('[Defenza Debug] watchPosition position object', {
          latitude: position?.coords?.latitude,
          longitude: position?.coords?.longitude,
          accuracy: position?.coords?.accuracy,
          altitude: position?.coords?.altitude,
          heading: position?.coords?.heading,
          speed: position?.coords?.speed,
          timestamp: position?.timestamp,
          source: position?.coords?.accuracy > 0 ? 'navigator.geolocation' : 'unknown'
        });
        if (!this.isPositionAccurate(position)) {
          console.warn('[Defenza Debug] watchPosition rejected coarse geolocation fix', {
            accuracy: Number(position?.coords?.accuracy ?? position?.acc ?? position?.accuracy ?? 0),
            threshold: this.getAccuracyThresholdMeters(),
            latitude: position?.coords?.latitude,
            longitude: position?.coords?.longitude
          });
          return;
        }
        const location = this.persistPosition(position, 'Current GPS Coordinates', 'active');
        if (location) {
          this.initMap(location.lat, location.lon, location.acc);
          this.updateUi(location.lat, location.lon, location.acc, location.address, location.status || 'active');
          this.syncLocationToServer(location, { skipIfRecent: true }).catch(() => {});
        }
      },
      (error) => {
        console.debug('[Defenza Debug] watchPosition error', {
          code: error?.code,
          message: error?.message,
          options: watchOptions
        });
        if (error.code === error.PERMISSION_DENIED && typeof showLocationPermissionBanner === 'function') {
          showLocationPermissionBanner(true);
        }
      },
      watchOptions
    );

    this.debugStats.activeWatchCount += 1;
    this.refreshLocation({ force: true, saveToDb: true, address: 'Current GPS Coordinates', showToast: false }).catch(() => {});

    return this.watchId;
  },

  stopTracking() {
    if (this.watchId !== null && navigator.geolocation && typeof navigator.geolocation.clearWatch === 'function') {
      navigator.geolocation.clearWatch(this.watchId);
    }
    if (this.debugStats.activeWatchCount > 0) {
      this.debugStats.activeWatchCount = Math.max(0, this.debugStats.activeWatchCount - 1);
    }
    this.watchId = null;
  },

  async ensureLocationForSos({ force = false } = {}) {
    const needsRefresh = force || !this.lastPosition || (Date.now() - (this.lastPositionAt || 0)) > 30000;
    if (!needsRefresh && this.lastPosition) {
      this.updateUi(this.lastPosition.lat, this.lastPosition.lon, this.lastPosition.acc, this.lastPosition.address, this.lastPosition.status || 'active');
      return this.lastPosition;
    }

    const location = await this.refreshLocation({ force: true, saveToDb: true, address: 'Current GPS Coordinates', showToast: false });
    if (!location) {
      return null;
    }

    return {
      lat: location.lat,
      lon: location.lon,
      acc: location.acc,
      address: location.address,
      latitude: location.lat,
      longitude: location.lon,
      accuracy: location.acc,
      timestamp: location.timestamp,
      status: location.status || 'active'
    };
  }
};

/**
 * Request the user's current location using HTML5 Geolocation.
 * This is a standalone module that can be safely pasted into main.js.
 */
function fetchUserLocationCoordinates() {
  if (!navigator.geolocation) {
    const warning = 'Geolocation API not supported in this browser.';
    console.warn(warning);
    window.DefenzaGeolocation.lastError = warning;
    return;
  }

  const geoOptions = {
    enableHighAccuracy: true,
    timeout: 15000,
    maximumAge: 0
  };

  navigator.geolocation.getCurrentPosition(
    handleUserLocationSuccess,
    handleUserLocationError,
    geoOptions
  );
}

/**
 * Success handler for live coordinates.
 * Stores values in global state and logs them to the console.
 */
function handleUserLocationSuccess(position) {
  const { latitude, longitude } = position.coords;

  window.DefenzaGeolocation.userLat = latitude;
  window.DefenzaGeolocation.userLng = longitude;
  window.userLat = latitude;
  window.userLng = longitude;
  window.DefenzaGeolocation.lastError = null;

  hideLocationPermissionBanner();

  console.log('Defenza live coordinates:', {
    latitude,
    longitude
  });
}

/**
 * Error handler for getCurrentPosition.
 * Handles permission, unavailable position, and timeout gracefully.
 */
function handleUserLocationError(error) {
  let message = 'Unable to retrieve location.';

  switch (error.code) {
    case error.PERMISSION_DENIED:
      message = 'Location access denied. Please enable location permissions for full safety coverage.';
      break;
    case error.POSITION_UNAVAILABLE:
      message = 'Position unavailable. Please try again in an open area.';
      break;
    case error.TIMEOUT:
      message = 'Location request timed out. Please retry.';
      break;
    default:
      message = 'Unexpected location error occurred.';
      break;
  }

  console.warn('Defenza location warning:', message, error);
  window.DefenzaGeolocation.lastError = message;
  if (typeof showToast === 'function') {
    showToast(message, 'warning', 5000);
  }

  if (error.code === error.PERMISSION_DENIED) {
    showLocationPermissionBanner(false);
    getGeolocationPermissionState().then((state) => {
      if (state === 'denied') {
        showLocationPermissionBanner(true);
      }
    });
  }
}

function getGeolocationPermissionState() {
  if (!navigator.permissions || typeof navigator.permissions.query !== 'function') {
    return Promise.resolve(null);
  }

  return navigator.permissions.query({ name: 'geolocation' })
    .then((status) => status.state)
    .catch(() => null);
}

function handleEnableLocationClick() {
  getGeolocationPermissionState().then((state) => {
    if (state === 'prompt' || state === null) {
      fetchUserLocationCoordinates();
      return;
    }

    if (state === 'granted') {
      hideLocationPermissionBanner();
      fetchUserLocationCoordinates();
      return;
    }

    if (state === 'denied') {
      showLocationPermissionBanner(true);
      showLocationHelpModal();
    }
  });
}

function injectLocationPermissionStyles() {
  if (document.getElementById('defenza-location-permission-styles')) return;

  const style = document.createElement('style');
  style.id = 'defenza-location-permission-styles';
  style.textContent = `
    #defenza-location-permission-banner {
      width: calc(100% - 2rem);
      max-width: 1180px;
      margin: 1rem auto;
      padding: 1rem 1.1rem;
      background: linear-gradient(180deg, rgba(254,243,199,0.95), rgba(253,230,138,0.95));
      border: 1px solid rgba(245,158,11,0.26);
      border-radius: var(--r-xl);
      box-shadow: var(--shadow-lg);
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 0.9rem;
      color: #111827;
      font-family: var(--font);
      animation: defenzaBannerIn var(--dur-fast) var(--ease-out);
      position: relative;
      z-index: 9999;
    }

    @keyframes defenzaBannerIn {
      from { opacity: 0; transform: translateY(-12px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .defenza-location-warning-main {
      display: flex;
      align-items: flex-start;
      gap: 0.9rem;
      flex: 1 1 320px;
      min-width: 0;
    }

    .defenza-location-warning-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: rgba(245, 158, 11, 0.16);
      color: #92400e;
      font-size: 1.2rem;
      flex-shrink: 0;
    }

    .defenza-location-warning-text {
      min-width: 0;
      line-height: 1.5;
    }

    .defenza-location-warning-text strong {
      display: block;
      font-size: 0.97rem;
      margin-bottom: 0.18rem;
      color: #111827;
    }

    .defenza-location-warning-text span {
      display: block;
      font-size: 0.9rem;
      color: #4b5563;
    }

    .defenza-location-warning-actions {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    .defenza-location-warning-button,
    .defenza-location-warning-link,
    .defenza-location-warning-close {
      border: none;
      cursor: pointer;
      font: inherit;
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .defenza-location-warning-button {
      padding: 0.72rem 1rem;
      border-radius: var(--r-full);
      background: var(--purple-700);
      color: #fff;
      font-weight: 700;
      box-shadow: var(--shadow-btn);
    }

    .defenza-location-warning-button:hover,
    .defenza-location-warning-button:focus {
      transform: translateY(-1px);
      outline: none;
      box-shadow: 0 14px 26px rgba(148, 64, 14, 0.24);
    }

    .defenza-location-warning-link {
      padding: 0.7rem 0.85rem;
      border-radius: 999px;
      background: rgba(15, 23, 42, 0.04);
      color: #111827;
      font-weight: 600;
    }

    .defenza-location-warning-link:hover,
    .defenza-location-warning-link:focus {
      text-decoration: underline;
      outline: none;
    }

    .defenza-location-warning-close {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: rgba(15, 23, 42, 0.05);
      color: #111827;
      font-size: 1.05rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .defenza-location-warning-close:hover,
    .defenza-location-warning-close:focus {
      background: rgba(15, 23, 42, 0.12);
      outline: none;
    }

    .defenza-location-permanent-note {
      width: 100%;
      margin-top: 0.8rem;
      padding: 0.85rem 1rem;
      background: rgba(15, 23, 42, 0.04);
      border-radius: 0.85rem;
      font-size: 0.85rem;
      color: #1f2937;
      line-height: 1.5;
    }

    .defenza-location-help-modal {
      position: fixed;
      inset: 0;
      z-index: 10000;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1.2rem;
      overflow-y: auto;
      background: rgba(15, 23, 42, 0.5);
    }

    .defenza-location-help-modal.open {
      display: flex;
    }

    .defenza-location-help-panel {
      width: min(680px, 100%);
      background: #ffffff;
      border-radius: 1rem;
      box-shadow: 0 30px 80px rgba(15, 23, 42, 0.16);
      padding: 1.5rem 1.5rem 1.25rem;
      color: #111827;
      overflow: hidden;
    }

    .defenza-location-help-panel h2 {
      margin: 0 0 0.75rem;
      font-size: 1.25rem;
      color: #111827;
    }

    .defenza-location-help-panel p,
    .defenza-location-help-panel li {
      color: #4b5563;
      line-height: 1.7;
      font-size: 0.95rem;
    }

    .defenza-location-help-panel ol {
      margin: 0.8rem 0 1.2rem 1.35rem;
      padding: 0;
    }

    .defenza-location-help-panel li {
      margin-bottom: 0.6rem;
    }

    .defenza-location-help-panel .note-box {
      margin-top: 1rem;
      padding: 1rem;
      border-radius: 0.85rem;
      border: 1px solid rgba(251, 191, 36, 0.35);
      background: rgba(254, 243, 199, 0.85);
      color: #92400e;
    }

    .defenza-location-help-panel .close-help-button {
      margin-top: 1rem;
      width: 100%;
      padding: 0.85rem 1rem;
      border-radius: 999px;
      border: none;
      background: #111827;
      color: #ffffff;
      font-weight: 700;
      cursor: pointer;
    }

    @media (max-width: 640px) {
      #defenza-location-permission-banner {
        width: calc(100% - 1rem);
        margin: 0.75rem auto;
        padding: 1rem;
      }

      .defenza-location-warning-actions {
        width: 100%;
        justify-content: stretch;
      }

      .defenza-location-warning-button,
      .defenza-location-warning-link {
        width: 100%;
      }
    }
  `;

  document.head.appendChild(style);
}

function createLocationPermissionBanner() {
  if (document.getElementById('defenza-location-permission-banner')) return;
  injectLocationPermissionStyles();

  const banner = document.createElement('section');
  banner.id = 'defenza-location-permission-banner';
  banner.setAttribute('role', 'status');
  banner.setAttribute('aria-live', 'polite');
  banner.innerHTML = `
    <div class="defenza-location-warning-main">
      <div class="defenza-location-warning-icon" aria-hidden="true">⚠️</div>
      <div class="defenza-location-warning-text">
        <strong>Location access is disabled. Enable it to use SOS Alerts, Live Location Sharing, and Nearby Safe Places.</strong>
      </div>
    </div>
    <div class="defenza-location-warning-actions">
      <button type="button" id="defenza-enable-location-btn" class="defenza-location-warning-button">Enable Location</button>
      <button type="button" id="defenza-show-location-help-btn" class="defenza-location-warning-link">How to Enable</button>
      <button type="button" id="defenza-dismiss-location-warning-btn" class="defenza-location-warning-close" aria-label="Dismiss location warning">×</button>
    </div>
    <div class="defenza-location-permanent-note" id="defenza-location-permanent-note" hidden></div>
  `;

  document.body.insertBefore(banner, document.body.firstChild);

  const enableButton = document.getElementById('defenza-enable-location-btn');
  const helpButton = document.getElementById('defenza-show-location-help-btn');
  const dismissButton = document.getElementById('defenza-dismiss-location-warning-btn');

  enableButton?.addEventListener('click', handleEnableLocationClick);
  helpButton?.addEventListener('click', function () {
    showLocationHelpModal();
  });
  dismissButton?.addEventListener('click', function () {
    hideLocationPermissionBanner();
  });
}

function showLocationPermissionBanner(permanentlyBlocked = false) {
  createLocationPermissionBanner();
  const note = document.getElementById('defenza-location-permanent-note');

  if (note) {
    if (permanentlyBlocked) {
      note.textContent = 'Location permission has been permanently blocked by your browser. Please enable location access from your browser\'s Site Settings to use emergency safety features.';
      note.hidden = false;
    } else {
      note.hidden = true;
    }
  }
}

function hideLocationPermissionBanner() {
  const banner = document.getElementById('defenza-location-permission-banner');
  if (banner) banner.remove();
}

function createLocationHelpModal() {
  if (document.getElementById('defenza-location-help-modal')) return;
  injectLocationPermissionStyles();

  const modal = document.createElement('div');
  modal.id = 'defenza-location-help-modal';
  modal.className = 'defenza-location-help-modal';
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-modal', 'true');
  modal.setAttribute('aria-labelledby', 'defenza-location-help-title');
  modal.innerHTML = `
    <div class="defenza-location-help-panel">
      <h2 id="defenza-location-help-title">How to Enable Location Permission</h2>
      <p>Open your browser settings and allow location access for this site to use emergency safety features.</p>
      <h3>Google Chrome</h3>
      <ol>
        <li>Click the 🔒 (Site Information) icon beside the website address.</li>
        <li>Open <strong>Site Settings</strong>.</li>
        <li>Find <strong>Location</strong>.</li>
        <li>Change it to <strong>Allow</strong>.</li>
        <li>Refresh the page.</li>
      </ol>
      <h3>Microsoft Edge</h3>
      <ol>
        <li>Click the 🔒 icon beside the website address.</li>
        <li>Open <strong>Permissions for this site</strong>.</li>
        <li>Set <strong>Location</strong> to <strong>Allow</strong>.</li>
        <li>Refresh the page.</li>
      </ol>
      <h3>Mozilla Firefox</h3>
      <ol>
        <li>Click the Site Information icon.</li>
        <li>Open <strong>Permissions</strong>.</li>
        <li>Allow <strong>Location Access</strong>.</li>
        <li>Refresh the page.</li>
      </ol>
      <div class="note-box"><strong>Note:</strong> If you selected <strong>Never Allow</strong> or <strong>Block</strong>, your browser will not display the permission popup again. You must manually enable location permission from your browser&apos;s Site Settings.</div>
      <button type="button" class="close-help-button" id="defenza-close-location-help-btn">Close</button>
    </div>
  `;

  modal.addEventListener('click', function (event) {
    if (event.target === modal) {
      hideLocationHelpModal();
    }
  });

  document.body.appendChild(modal);
  document.getElementById('defenza-close-location-help-btn')?.addEventListener('click', hideLocationHelpModal);
}

function showLocationHelpModal() {
  createLocationHelpModal();
  const modal = document.getElementById('defenza-location-help-modal');
  if (modal) modal.classList.add('open');
}

function hideLocationHelpModal() {
  const modal = document.getElementById('defenza-location-help-modal');
  if (modal) modal.classList.remove('open');
}

function setLiveLocationStatus(active) {
  const statusEl = document.getElementById('liveStatusText');
  if (!statusEl) return;
  if (active) {
    statusEl.textContent = 'Active — updating every 10s';
    statusEl.style.color = '#16a34a';
  } else {
    statusEl.textContent = 'Paused — sharing stopped';
    statusEl.style.color = '#dc2626';
  }
}

// ── Mobile Navbar ─────────────────────────────────────────
function toggleMobileMenu() {
  const menu   = document.getElementById('mobileMenu');
  const icon   = document.querySelector('.navbar-toggler i');
  if (!menu) return;
  const isOpen = menu.classList.toggle('open');
  if (icon) icon.className = isOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
}

document.addEventListener('click', function(e) {
  const menu   = document.getElementById('mobileMenu');
  const toggle = document.querySelector('.navbar-toggler');
  if (menu && menu.classList.contains('open') && !menu.contains(e.target) && !toggle?.contains(e.target)) {
    menu.classList.remove('open');
    const icon = document.querySelector('.navbar-toggler i');
    if (icon) icon.className = 'fa-solid fa-bars';
  }
});

// ── Dashboard Sidebar ─────────────────────────────────────
function openSidebar() {
  document.getElementById('sidebar')?.classList.add('open');
  document.getElementById('sidebarOverlay')?.classList.add('show');
  document.body.style.overflow = 'hidden';
}

function closeSidebar() {
  document.getElementById('sidebar')?.classList.remove('open');
  document.getElementById('sidebarOverlay')?.classList.remove('show');
  document.body.style.overflow = '';
}

// ── Password Toggle ───────────────────────────────────────
function togglePass(inputId, btn) {
  const input  = document.getElementById(inputId);
  if (!input) return;
  const isPass = input.type === 'password';
  input.type   = isPass ? 'text' : 'password';
  const icon   = btn.querySelector('i');
  if (icon) icon.className = isPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
}

// ── Modal ──────────────────────────────────────────────────
function openModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
}

function closeModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
}

document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('show');
    document.body.style.overflow = '';
  }
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.show').forEach(m => {
      m.classList.remove('show');
      document.body.style.overflow = '';
    });
  }
});

// ── Toast Notifications ───────────────────────────────────
function showToast(message, type = 'success', duration = 3800) {
  let tc = document.getElementById('toastContainer');
  if (!tc) {
    tc = document.createElement('div');
    tc.id = 'toastContainer';
    tc.className = 'toast-container';
    document.body.appendChild(tc);
  }
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  const icons  = { success: 'fa-circle-check', error: 'fa-circle-xmark', info: 'fa-circle-info', warning: 'fa-triangle-exclamation' };
  const colors = { success: '#10b981', error: '#ef4444', info: '#6366f1', warning: '#f59e0b' };
  toast.innerHTML = `
    <i class="fa-solid ${icons[type] || 'fa-circle-info'}" style="color:${colors[type]||'#6366f1'};font-size:1.05rem;flex-shrink:0;"></i>
    <span style="flex:1;font-size:.83rem;font-weight:600;color:#111827;">${message}</span>
    <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:.9rem;padding:.1rem .2rem;border-radius:4px;line-height:1;flex-shrink:0;">
      <i class="fa-solid fa-xmark"></i>
    </button>
  `;
  tc.appendChild(toast);
  setTimeout(() => {
    toast.style.animation = 'toast-out .35s ease forwards';
    setTimeout(() => toast.remove(), 350);
  }, duration);
}

// ── Scroll Animations ─────────────────────────────────────
function initScrollAnimations() {
  const targets = document.querySelectorAll(
    '.feature-card, .stat-box, .card-defenza, .tip-card, .team-card, .contact-info-card, .stagger-child'
  );
  if (!('IntersectionObserver' in window)) {
    targets.forEach(el => { el.style.opacity = '1'; });
    return;
  }
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        const el = entry.target;
        const delay = el.dataset.delay || 0;
        setTimeout(() => {
          el.style.animation = 'page-fade-in .5s cubic-bezier(0.16,1,0.3,1) forwards';
        }, delay);
        observer.unobserve(el);
      }
    });
  }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });

  targets.forEach((el, i) => {
    if (!el.style.opacity) el.style.opacity = '0';
    if (!el.dataset.delay) el.dataset.delay = Math.min(i * 60, 360);
    observer.observe(el);
  });
}

// ── Smooth scroll for anchor links ────────────────────────
document.addEventListener('click', function(e) {
  const a = e.target.closest('a[href^="#"]');
  if (!a) return;
  const target = document.querySelector(a.getAttribute('href'));
  if (target) {
    e.preventDefault();
    const offset = 80;
    const top = target.getBoundingClientRect().top + window.scrollY - offset;
    window.scrollTo({ top, behavior: 'smooth' });
  }
});

// ── Navbar scroll effect ──────────────────────────────────
window.addEventListener('scroll', function() {
  const navbar = document.querySelector('.public-navbar');
  if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 30);
}, { passive: true });

// ── Counter animation ─────────────────────────────────────
function animateCounter(el, target, suffix = '', prefix = '') {
  const duration = 1400;
  const easeOut  = t => 1 - Math.pow(1 - t, 3);
  let startTime  = null;

  const step = (timestamp) => {
    if (!startTime) startTime = timestamp;
    const elapsed  = timestamp - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const value    = Math.floor(easeOut(progress) * target);
    el.textContent = prefix + value + suffix;
    if (progress < 1) requestAnimationFrame(step);
    else el.textContent = prefix + target + suffix;
  };
  requestAnimationFrame(step);
}

// ── Form validation styling ────────────────────────────────
function initFormValidation() {
  document.querySelectorAll('form').forEach(form => {
    form.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
      field.addEventListener('blur', function() {
        if (!this.value.trim()) this.classList.add('is-invalid');
        else this.classList.remove('is-invalid');
      });
      field.addEventListener('input', function() {
        if (this.value.trim()) this.classList.remove('is-invalid');
      });
    });
  });
}

// ── Demo: Auth form handler ───────────────────────────────
function initAuthForms() {
  document.querySelectorAll('.auth-form').forEach(form => {
    form.addEventListener('submit', function() {
      const btn = this.querySelector('[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Please wait…';
      }
    });
  });
}

// ── Demo: Dashboard form handler ─────────────────────────
function initDemoForms() {
  document.querySelectorAll('.demo-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      const msg      = this.dataset.success || 'Saved successfully!';
      const redirect = this.dataset.redirect;
      const btn = this.querySelector('[type="submit"]');
      const origHtml = btn ? btn.innerHTML : '';
      if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving…'; }
      showToast(msg, 'success');
      setTimeout(() => {
        if (redirect) { window.location.href = redirect; return; }
        if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
      }, 1000);
    });
  });
}

// ── SOS button demo flow ──────────────────────────────────
function initSOSDemo() {
  const sosBtn = document.getElementById('sosBtnDemo');
  if (!sosBtn || sosBtn.dataset.sosBound === 'true') return;
  sosBtn.dataset.sosBound = 'true';

  sosBtn.addEventListener('click', async function() {
    const contactCount = parseInt(this.dataset.contactCount, 10) || 0;
    const sosUrl = this.dataset.sosUrl;
    if (contactCount === 0) {
      showToast('No emergency contacts found. Add at least one contact before sending an SOS alert.', 'error', 5000);
      return;
    }

    if (!confirm('⚠️ Send SOS Alert?\n\nThis will notify your emergency contacts immediately.')) return;

    const origHtml = this.innerHTML;
    this.disabled = true;
    this.innerHTML = '<span class="spinner" style="border-color:rgba(255,255,255,.3);border-top-color:#fff;width:32px;height:32px;border-width:3px;"></span>';

    const coords = await (async () => {
      if (!navigator.geolocation) {
        return null;
      }
      if (window.DefenzaLiveLocationService && typeof window.DefenzaLiveLocationService.getVerifiedLocation === 'function') {
        const location = await window.DefenzaLiveLocationService.getVerifiedLocation({ force: false, saveToDb: true, address: 'Current GPS Coordinates' });
        if (location && Number.isFinite(location.acc)) {
          console.debug('[Defenza Debug] Demo SOS verified location accuracy (m):', location.acc);
        }
        return location ? { latitude: location.lat, longitude: location.lon, accuracy: location.acc } : null;
      }
      return new Promise(resolve => {
        navigator.geolocation.getCurrentPosition(
          position => {
            console.debug('[Defenza Debug] Demo SOS fallback position accuracy (m):', position?.coords?.accuracy);
            resolve(position.coords);
          },
          () => resolve(null),
          { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
      });
    })();

    const address = coords ? 'Live location' : 'Unknown location';
    const formData = new FormData();
    if (coords) {
      formData.append('lat', coords.latitude);
      formData.append('lon', coords.longitude);
    }
    formData.append('address', address);

    try {
      const response = await fetch(sosUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      });
      const data = await response.json();

      if (!data.success) {
        throw new Error(data.message || 'Failed to send SOS alert');
      }

      const label = data.contacts_notified === 1 ? 'contact' : 'contacts';
      showToast(`🚨 SOS Alert sent! ${data.contacts_notified} ${label} notified.`, 'success', 5000);
      const successEl = document.getElementById('sosSuccess');
      const btnWrap   = document.getElementById('sosBtnWrap');
      if (successEl && btnWrap) {
        btnWrap.style.display   = 'none';
        successEl.style.display = 'block';
        successEl.style.animation = 'page-fade-in .4s ease';
      }
    } catch (error) {
      showToast(error.message, 'error');
    } finally {
      if (this.disabled) {
        this.disabled = false;
        this.innerHTML = origHtml;
      }
    }
  });
}

// ── Demo: Contact actions ─────────────────────────────────
function demoDeleteContact(name) {
  if (!confirm(`Remove "${name}" from emergency contacts?`)) return;
  showToast(`"${name}" removed from contacts.`, 'success');
}

// ── Fake call simulation module ─────────────────────────────────────
function initFakeCallPage() {
  const wrapper = document.getElementById('fakeCallPage');
  if (!wrapper) return;

  const form = document.getElementById('fakeCallForm');
  const startBtn = document.getElementById('startFakeCall');
  const countdownText = document.getElementById('fakeCallCountdown');
  const overlay = document.getElementById('incomingCallOverlay');
  const callerName = document.getElementById('incomingCallerName');
  const ringtone = { context: null, oscillator: null };

  function formatTime(seconds) {
    return seconds > 59 ? `${Math.floor(seconds / 60)} min ${seconds % 60}s` : `${seconds}s`;
  }

  function stopRingtone() {
    if (ringtone.oscillator) {
      ringtone.oscillator.stop();
      ringtone.oscillator.disconnect();
      ringtone.oscillator = null;
    }
    if (ringtone.context) {
      ringtone.context.close();
      ringtone.context = null;
    }
  }

  function playRingtone() {
    try {
      ringtone.context = new (window.AudioContext || window.webkitAudioContext)();
      ringtone.oscillator = ringtone.context.createOscillator();
      const gain = ringtone.context.createGain();
      ringtone.oscillator.type = 'triangle';
      ringtone.oscillator.frequency.value = 480;
      gain.gain.value = 0.12;
      ringtone.oscillator.connect(gain);
      gain.connect(ringtone.context.destination);
      ringtone.oscillator.start();
      window.setTimeout(stopRingtone, 5500);
    } catch (error) {
      console.warn('Ringtone unavailable:', error);
    }
  }

  function showIncomingCall(name) {
    callerName.textContent = name;
    overlay.classList.add('show');
    playRingtone();
  }

  function closeIncomingCall() {
    overlay.classList.remove('show');
    stopRingtone();
  }

  form?.addEventListener('submit', function (event) {
    event.preventDefault();
    const caller = document.getElementById('callerId')?.value || 'Friend';
    const callAfter = parseInt(document.getElementById('callAfter')?.value || '5', 10);

    startBtn.disabled = true;
    startBtn.textContent = 'Preparing…';
    let remaining = callAfter;
    countdownText.textContent = `Your fake call will start in ${formatTime(remaining)}.`;

    const timer = window.setInterval(() => {
      remaining -= 1;
      if (remaining <= 0) {
        window.clearInterval(timer);
        startBtn.textContent = 'Start Fake Call';
        startBtn.disabled = false;
        countdownText.textContent = 'Incoming call simulation started.';
        showIncomingCall(caller);
        return;
      }
      countdownText.textContent = `Your fake call will start in ${formatTime(remaining)}.`;
    }, 1000);
  });

  document.getElementById('acceptCall')?.addEventListener('click', function () {
    closeIncomingCall();
    showToast('Call accepted — use this to transition safely from the situation.', 'success', 4200);
  });
  document.getElementById('declineCall')?.addEventListener('click', function () {
    closeIncomingCall();
    showToast('Call declined — the fake incoming call has ended.', 'info', 3200);
  });
}

// ── Nearby safe places module ──────────────────────────────────────
function initNearbyPlacesPage() {
  const page = document.getElementById('nearbyPlacesPage');
  if (!page) return;

  const listContainer = document.getElementById('nearbyPlacesList');
  const mapContainer = document.getElementById('leafletMap');
  const mapStatus = document.getElementById('mapStatus');
  const refreshBtn = document.getElementById('refreshNearbyPlaces');
  const centerMapBtn = document.getElementById('centerMapBtn');
  const toggleMapBtn = document.getElementById('toggleMapView');

  let map = null;
  let markers = {};
  let userMarker = null;
  let currentPlaces = [];
  let userLocation = null;
  let currentFilter = 'all';

  // Initialize Leaflet map
  function initMap() {
    if (map) return;
    map = L.map('leafletMap').setView([33.7100, 73.0600], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
      maxZoom: 19
    }).addTo(map);
  }

  // Create custom icons
  const getIcon = (category) => {
    const colors = {
      police: '#2563eb',
      hospital: '#0ea5e9',
      helpline: '#ec4899'
    };
    return L.divIcon({
      html: `<div style="background:${colors[category]};width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;box-shadow:0 4px 12px rgba(0,0,0,0.3);">
        ${category === 'police' ? '<i class="fa-solid fa-shield"></i>' : category === 'hospital' ? '<i class="fa-solid fa-hospital"></i>' : '<i class="fa-solid fa-phone"></i>'}
      </div>`,
      className: 'map-icon',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
      popupAnchor: [0, -10]
    });
  };

  async function fetchPlaces(lat, lng) {
    mapStatus.classList.add('show');
    mapStatus.textContent = 'Loading nearby safe zones…';
    const url = new URL(`${document.querySelector('meta[name="app-url"]')?.content || ''}/api/nearby-places.php`);
    if (lat && lng) {
      url.searchParams.set('lat', lat);
      url.searchParams.set('lng', lng);
    }
    try {
      const response = await fetch(url.toString());
      const data = await response.json();
      if (!data.success) throw new Error(data.message || 'Unable to load places.');
      setTimeout(() => mapStatus.classList.remove('show'), 2000);
      return data.places;
    } catch (error) {
      mapStatus.textContent = 'Error loading places';
      showToast(error.message || 'Could not load safe places.', 'error');
      return [];
    }
  }

  function addPlaceMarkers(places) {
    // Clear old markers
    Object.values(markers).forEach(marker => marker.remove());
    markers = {};

    places.forEach((place, idx) => {
      const marker = L.marker(
        [place.latitude, place.longitude],
        { icon: getIcon(place.category) }
      ).addTo(map);

      const popupContent = `
        <div style="font-size:13px;min-width:220px;">
          <div style="font-weight:700;color:#111827;margin-bottom:6px;">${place.name}</div>
          <div style="color:#6b7280;margin-bottom:8px;font-size:12px;">
            <div><i class="fa-solid fa-location-dot"></i> ${place.address}</div>
            <div style="margin-top:4px;"><i class="fa-solid fa-ruler"></i> ${place.distance} km away</div>
          </div>
          <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a href="tel:${place.phone}" class="btn-defenza btn-primary btn-sm" style="font-size:11px;padding:6px 12px;flex:1;">
              <i class="fa-solid fa-phone"></i> Call
            </a>
            <button onclick="window.open('https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(place.latitude + ',' + place.longitude)}', '_blank')" class="btn-defenza btn-secondary btn-sm" style="font-size:11px;padding:6px 12px;flex:1;border:1px solid #e5e7eb;">
              <i class="fa-solid fa-map"></i> Maps
            </button>
          </div>
        </div>
      `;
      marker.bindPopup(popupContent, { maxWidth: 250 });
      marker.on('click', () => {
        Object.values(markers).forEach(m => {
          if (m !== marker) {
            m.closePopup();
            m.getElement()?.classList.remove('active-marker');
          }
        });
        marker.getElement()?.classList.add('active-marker');
      });
      markers[place.id] = marker;
    });
  }

  function updateStats(places) {
    const counts = {
      police: places.filter(p => p.category === 'police').length,
      hospital: places.filter(p => p.category === 'hospital').length,
      helpline: places.filter(p => p.category === 'helpline').length
    };
    document.getElementById('statPolice').textContent = counts.police;
    document.getElementById('statHospital').textContent = counts.hospital;
    document.getElementById('statHelpline').textContent = counts.helpline;
  }

  function renderPlaces(places) {
    listContainer.innerHTML = '';
    currentPlaces = places;
    updateStats(places);

    if (!places.length) {
      listContainer.innerHTML = '<div class="card-defenza" style="padding:2rem;text-align:center;color:#6b7280;grid-column:1/-1;"><i class="fa-solid fa-inbox" style="font-size:2.5rem;margin-bottom:1rem;opacity:.4;display:block;"></i> No safe places found in your area.</div>';
      return;
    }

    places.forEach(place => {
      const item = document.createElement('article');
      item.className = 'safe-place-card';
      item.dataset.category = place.category;
      
      const categoryIcons = {
        police: 'fa-shield',
        hospital: 'fa-hospital',
        helpline: 'fa-phone'
      };

      item.innerHTML = `
        <div style="display:flex;justify-content:space-between;gap:1rem;align-items:start;">
          <div style="flex:1;">
            <span class="place-category ${place.category}">
              <i class="fa-solid ${categoryIcons[place.category]}"></i> ${place.category_label}
            </span>
            <h4 style="margin:.5rem 0 .35rem;color:#111827;font-size:1.05rem;">${place.name}</h4>
            <p style="margin:0;color:#6b7280;font-size:.85rem;display:flex;align-items:center;gap:.35rem;">
              <i class="fa-solid fa-map-pin"></i> ${place.address}
            </p>
          </div>
          <div style="text-align:right;flex-shrink:0;">
            <div class="distance-badge">
              <i class="fa-solid fa-location-dot"></i> ${parseFloat(place.distance).toFixed(1)} km
            </div>
          </div>
        </div>
        
        <div style="border-top:1px solid #e5e7eb;margin-top:1rem;padding-top:1rem;display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
          <a href="tel:${place.phone}" class="btn-defenza btn-primary" style="justify-content:center;">
            <i class="fa-solid fa-phone"></i> Call Now
          </a>
          <button class="btn-defenza btn-secondary" onclick="window.open('https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(place.name + ', ' + place.address)}', '_blank')">
            <i class="fa-solid fa-map"></i> Get Directions
          </button>
        </div>
      `;
      listContainer.appendChild(item);
    });

    applyFilters();
  }

  function applyFilters() {
    const filtered = currentFilter === 'all' 
      ? currentPlaces 
      : currentPlaces.filter(p => p.category === currentFilter);
    
    document.querySelectorAll('.safe-place-card').forEach(card => {
      card.style.display = currentFilter === 'all' || card.dataset.category === currentFilter ? 'grid' : 'none';
    });

    addPlaceMarkers(filtered);
  }

  function centerMapOnUser() {
    if (userLocation && map) {
      map.setView([userLocation.latitude, userLocation.longitude], 15);
    }
  }

  async function refreshPlaces() {
    initMap();
    
    if (!navigator.geolocation) {
      mapStatus.textContent = 'Geolocation unsupported. Showing default zones.';
      renderPlaces(await fetchPlaces());
      return;
    }

    mapStatus.classList.add('show');
    mapStatus.textContent = 'Getting your location…';
    navigator.geolocation.getCurrentPosition(async (position) => {
      const { latitude, longitude } = position.coords;
      userLocation = { latitude, longitude };

      // Add user location marker
      if (userMarker) userMarker.remove();
      userMarker = L.marker([latitude, longitude], {
        icon: L.divIcon({
          html: `<div style="background:linear-gradient(135deg,#7c3aed,#ec4899);width:40px;height:40px;border-radius:50%;border:4px solid #fff;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;box-shadow:0 0 0 2px #7c3aed,0 6px 20px rgba(124,58,237,0.4);">
            <i class="fa-solid fa-location-dot"></i>
          </div>`,
          className: 'user-location-marker',
          iconSize: [40, 40],
          iconAnchor: [20, 20]
        })
      }).addTo(map);

      mapStatus.textContent = `Your location: ${latitude.toFixed(4)}, ${longitude.toFixed(4)}`;
      map.setView([latitude, longitude], 14);
      setTimeout(() => mapStatus.classList.remove('show'), 2000);
      
      renderPlaces(await fetchPlaces(latitude, longitude));
    }, async () => {
      mapStatus.textContent = 'Using default location';
      setTimeout(() => mapStatus.classList.remove('show'), 2000);
      renderPlaces(await fetchPlaces());
    }, { enableHighAccuracy: true, maximumAge: 0, timeout: 15000 });
  }

  // Toggle map view
  let mapVisible = true;
  function toggleMapVisibility() {
    const mapContainer = document.getElementById('mapContainer');
    const placesContainer = document.querySelector('.places-container');
    
    if (!mapContainer || !placesContainer) return;
    
    mapVisible = !mapVisible;
    
    if (mapVisible) {
      mapContainer.style.display = 'block';
      toggleMapBtn.innerHTML = '<i class="fa-solid fa-list"></i> View List';
      toggleMapBtn.title = 'Show places list view';
      if (map) map.invalidateSize();
    } else {
      mapContainer.style.display = 'none';
      toggleMapBtn.innerHTML = '<i class="fa-solid fa-map"></i> View Map';
      toggleMapBtn.title = 'Show map view';
    }
  }

  // Event listeners
  refreshBtn?.addEventListener('click', refreshPlaces);
  centerMapBtn?.addEventListener('click', centerMapOnUser);
  toggleMapBtn?.addEventListener('click', toggleMapVisibility);
  
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      currentFilter = this.dataset.filter;
      applyFilters();
    });
  });

  // Initialize
  refreshPlaces();
}

// ── Detection settings module ─────────────────────────────────────
function initDetectionSettingsPage() {
  const page = document.getElementById('detectionSettingsPage');
  if (!page) return;

  const form = document.getElementById('detectionSettingsForm');
  const sensitivityInput = document.getElementById('shakeSensitivity');
  const sensitivityLabel = document.getElementById('shakeSensitivityLabel');
  const sensitivityBar = document.querySelector('.sensitivity-meter span');
  const voiceToggle = document.getElementById('voiceEnabled');
  const shakeToggle = document.getElementById('shakeEnabled');
  const keywordInput = document.getElementById('emergency_keyword');
  const statusArea = document.getElementById('detectionSettingsStatus');
  const apiUrl = page.dataset.apiUrl;

  function updateSensitivityDisplay(value) {
    const labels = { 1: 'Low', 2: 'Medium', 3: 'High' };
    const width = value * 33.333;
    if (sensitivityBar) sensitivityBar.style.width = `${width}%`;
    if (sensitivityLabel) sensitivityLabel.textContent = labels[value] || 'Medium';
  }

  function updateFormState() {
    if (keywordInput) {
      keywordInput.readOnly = !voiceToggle?.checked;
      keywordInput.style.opacity = voiceToggle?.checked ? '1' : '0.65';
    }
    if (sensitivityInput) {
      sensitivityInput.style.opacity = shakeToggle?.checked ? '1' : '0.55';
    }
  }

  async function submitDetectionSettings(event) {
    if (!form || !apiUrl) return;
    event.preventDefault();
    const data = new URLSearchParams(new FormData(form));
    const saveButton = document.getElementById('detectionSaveButton');

    if (saveButton) {
      saveButton.disabled = true;
      saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
    }

    statusArea.textContent = 'Saving detection settings...';

    try {
      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: data.toString(),
      });
      const result = await response.json();

      if (!result.success) {
        throw new Error(result.message || 'Unable to save settings.');
      }

      showToast('Detection settings saved successfully.', 'success');
      statusArea.textContent = 'Settings saved successfully.';
      statusArea.style.color = '#047857';
    } catch (error) {
      showToast(error.message, 'error');
      statusArea.textContent = error.message || 'Unable to save settings.';
      statusArea.style.color = '#b91c1c';
    } finally {
      if (saveButton) {
        saveButton.disabled = false;
        saveButton.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Detection Settings';
      }
    }
  }

  sensitivityInput?.addEventListener('input', function () {
    updateSensitivityDisplay(this.value);
  });

  voiceToggle?.addEventListener('change', updateFormState);
  shakeToggle?.addEventListener('change', updateFormState);
  form?.addEventListener('submit', submitDetectionSettings);

  updateSensitivityDisplay(sensitivityInput?.value || 2);
  updateFormState();
}

// ── Helpline chat module ─────────────────────────────────────
function initHelplineChatPage() {
  const page = document.getElementById('helplineChatPage');
  if (!page) return;

  const messageList = document.getElementById('chatMessages');
  const statusDisplay = document.getElementById('chatStatus');
  const form = document.getElementById('helplineChatForm');
  const messageInput = document.getElementById('chatMessageInput');
  const sendBtn = document.getElementById('chatSendBtn');
  const typingIndicator = document.getElementById('typingIndicator');
  const apiUrl = page.dataset.apiUrl;
  let offlineMode = false;

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function setChatStatus(text, type = 'info') {
    if (!statusDisplay) return;
    const icons = {
      info: 'fa-circle-info',
      success: 'fa-circle-check',
      error: 'fa-circle-xmark',
      warning: 'fa-triangle-exclamation'
    };
    statusDisplay.innerHTML = `
      <i class="fa-solid ${icons[type] || 'fa-circle-info'}" style="flex-shrink:0;"></i>
      <span>${escapeHtml(text)}</span>
    `;
    statusDisplay.dataset.status = type;
  }

  function buildBubble(message) {
    const el = document.createElement('div');
    el.className = `chat-message ${message.sender}`;
    const senderName = message.sender === 'user' ? 'You' : (message.operator_name || 'Support');
    const senderIcon = message.sender === 'user' ? 'fa-user' : 'fa-headset';
    
    el.innerHTML = `
      <div class="meta">
        <i class="fa-solid ${senderIcon}" style="font-size:.65rem;"></i>
        <strong>${escapeHtml(senderName)}</strong>
        <span>${escapeHtml(message.created_at)}</span>
      </div>
      <div>${escapeHtml(message.message)}</div>
    `;
    return el;
  }

  function renderMessages(messages) {
    if (!messageList) return;
    messageList.innerHTML = '';
    if (!messages || messages.length === 0) {
      const emptyMsg = document.createElement('div');
      emptyMsg.style.cssText = 'padding:2rem;text-align:center;color:#9ca3af;';
      emptyMsg.innerHTML = '<i class="fa-solid fa-inbox" style="font-size:2rem;margin-bottom:.5rem;display:block;opacity:.5;"></i><p>No messages yet. Start a conversation!</p>';
      messageList.appendChild(emptyMsg);
      return;
    }
    messages.forEach(msg => messageList.appendChild(buildBubble(msg)));
    scrollToBottom();
  }

  function scrollToBottom() {
    if (!messageList) return;
    setTimeout(() => {
      messageList.scrollTop = messageList.scrollHeight;
    }, 50);
  }

  async function loadChatHistory() {
    setChatStatus('Connecting to support…', 'info');
    try {
      const response = await fetch(apiUrl || '/api/helpline-chat.php');
      const data = await response.json();
      if (!data.success) {
        throw new Error(data.message || 'Unable to load chat history.');
      }

      offlineMode = !!data.offline;
      if (offlineMode) {
        setChatStatus('Demo mode active — messages are simulated.', 'warning');
        showToast('Database unavailable. Using demo mode for testing.', 'warning');
      } else {
        setChatStatus('Live support connected.', 'success');
      }

      renderMessages(data.messages);
    } catch (error) {
      setChatStatus('Connection error. Please refresh and try again.', 'error');
      renderMessages([
        { 
          sender: 'support', 
          message: 'We\'re experiencing technical difficulties. Please try again or contact emergency services if urgent: Police 100, Women Helpline 1091, Ambulance 108.', 
          operator_name: 'System', 
          created_at: 'Now' 
        },
      ]);
      showToast(error.message, 'error');
    }
  }

  async function sendMessage(event) {
    event.preventDefault();
    const text = messageInput.value.trim();
    if (!text) {
      showToast('Please type a message.', 'info');
      return;
    }
    if (!apiUrl) {
      showToast('Chat service not configured.', 'error');
      return;
    }

    messageInput.disabled = true;
    sendBtn.disabled = true;
    const originalBtnHtml = sendBtn.innerHTML;
    sendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending…';

    const payload = new URLSearchParams({ message: text });

    try {
      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload.toString(),
      });
      const data = await response.json();
      if (!data.success) throw new Error(data.message || 'Unable to send message.');

      offlineMode = !!data.offline;
      if (offlineMode) {
        setChatStatus('Demo mode — not sent to real database.', 'warning');
        renderMessages(data.messages);
      } else {
        setChatStatus('Message delivered to support.', 'success');
        await loadChatHistory();
      }
      messageInput.value = '';
      showToast('Message sent successfully.', 'success');
      messageInput.focus();
    } catch (error) {
      showToast(error.message || 'Failed to send message.', 'error');
      setChatStatus('Message not sent — try again.', 'error');
    } finally {
      messageInput.disabled = false;
      sendBtn.disabled = false;
      sendBtn.innerHTML = originalBtnHtml;
      messageInput.focus();
    }
  }

  form?.addEventListener('submit', sendMessage);
  messageInput?.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'Enter') {
      form?.dispatchEvent(new Event('submit'));
    }
  });

  loadChatHistory();
}


function demoContactSave() {
  closeModal('addModal');
  closeModal('editModal');
  showToast('Contact saved successfully!', 'success');
}

// ── Tab switching ─────────────────────────────────────────
function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  const btn = document.querySelector(`[data-tab="${tab}"]`);
  const pane = document.getElementById('tab-' + tab);
  if (btn) btn.classList.add('active');
  if (pane) pane.classList.add('active');
  history.replaceState(null, '', '?tab=' + tab);
}

// ── Contact modal helpers ─────────────────────────────────
function openEditModal(id, name, rel, phone) {
  document.getElementById('editContactId').value    = id;
  document.getElementById('editName').value         = name;
  document.getElementById('editPhone').value        = phone;
  const sel = document.getElementById('editRelationship');
  if (sel) for (const o of sel.options) o.selected = (o.value === rel);
  openModal('editModal');
}

function openDeleteModal(id, name) {
  document.getElementById('deleteContactId').value    = id;
  const el = document.getElementById('deleteContactName');
  if (el) el.textContent = `This will remove "${name}" from your emergency contacts.`;
  openModal('deleteModal');
}

// ── Location helper ───────────────────────────────────────
function formatCoordinate(lat, lon) {
  const latHem = lat >= 0 ? 'N' : 'S';
  const lonHem = lon >= 0 ? 'E' : 'W';
  return `${Math.abs(lat).toFixed(4)}°${latHem}`;
}

function formatCoordinateLon(lon) {
  const lonHem = lon >= 0 ? 'E' : 'W';
  return `${Math.abs(lon).toFixed(4)}°${lonHem}`;
}

function getLiveLocationApiUrl() {
  const page = document.getElementById('liveLocationPage');
  return page?.dataset.apiUrl || '/api/live-location.php';
}

function updateLiveLocationUI(lat, lon, acc, address = 'Current GPS Coordinates') {
  const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
  set('latVal', formatCoordinate(lat, lon));
  set('lonVal', formatCoordinateLon(lon));
  set('accVal', `${Math.round(acc)} m`);
  set('timeVal', new Date().toLocaleTimeString());
  const label = document.getElementById('locationLabel');
  if (label) {
    label.textContent = `Lat ${lat.toFixed(4)}, Lon ${lon.toFixed(4)}`;
  }
  const sidebarAddress = document.getElementById('sidebarAddress');
  const sidebarDetail = document.getElementById('sidebarAddressDetail');
  if (sidebarAddress) {
    sidebarAddress.textContent = address;
  }
  if (sidebarDetail) {
    sidebarDetail.textContent = `${Math.abs(lat).toFixed(4)}° ${lat >= 0 ? 'N' : 'S'}, ${Math.abs(lon).toFixed(4)}° ${lon >= 0 ? 'E' : 'W'}`;
  }
}

async function fetchSavedLiveLocation() {
  try {
    const apiUrl = getLiveLocationApiUrl();
    const response = await fetch(apiUrl, { credentials: 'same-origin' });
    const data = await response.json();
    if (data.success && data.location && typeof data.location.lat === 'number' && typeof data.location.lon === 'number') {
      updateLiveLocationUI(data.location.lat, data.location.lon, data.location.acc || 0, data.location.address || 'Saved live location');
      setLiveLocationStatus(data.location.status !== 'paused');
      return data.location;
    }
  } catch (error) {
    // ignore failures on initial load
  }
  return null;
}

async function sendLiveLocationToServer(location) {
  const apiUrl = getLiveLocationApiUrl();
  const formData = new FormData();
  formData.append('lat', location.lat);
  formData.append('lon', location.lon);
  if (location.acc != null) formData.append('acc', location.acc);
  if (location.address) formData.append('address', location.address);
  if (location.status) formData.append('status', location.status);

  const response = await fetch(apiUrl, {
    method: 'POST',
    credentials: 'same-origin',
    body: formData,
  });
  const data = await response.json();
  if (!data.success) {
    throw new Error(data.message || 'Unable to save live location.');
  }
  return data.location;
}

function refreshLocation(showToastOnSuccess = true, saveToDb = true) {
  const btn = document.getElementById('refreshBtn');
  const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
  const updateLocation = async (lat, lon, acc) => {
    updateLiveLocationUI(lat, lon, acc, 'Current GPS Coordinates');
    if (btn) btn.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i>';
    if (saveToDb) {
      try {
        await sendLiveLocationToServer({
          lat,
          lon,
          acc,
          address: 'Current GPS Coordinates',
          status: 'active',
        });
      } catch (error) {
        if (showToastOnSuccess) {
          showToast(error.message, 'warning');
        }
      }
    }
    if (showToastOnSuccess) {
      showToast('Location updated!', 'success');
    }
  };

  if (btn) { btn.innerHTML = '<i class="fa-solid fa-arrows-rotate" style="animation:spin .7s linear infinite;display:inline-block;"></i>'; }
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      pos => updateLocation(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy),
      err => {
        set('latVal', 'Unavailable');
        set('lonVal', 'Unavailable');
        set('accVal', 'N/A');
        set('timeVal', 'Error');
        const label = document.getElementById('locationLabel');
        if (label) label.textContent = 'Unable to get location';
        const sidebarAddress = document.getElementById('sidebarAddress');
        if (sidebarAddress) sidebarAddress.textContent = 'Location unavailable';
        if (btn) btn.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i>';
        const permissionMessage = err && typeof err.code === 'number' && err.code === err.PERMISSION_DENIED;
        const message = permissionMessage
          ? 'Unable to fetch your live location. Please allow location access.'
          : 'Unable to fetch your live location right now. Retrying in the background.';
        showToast(message, permissionMessage ? 'error' : 'warning');
      },
      { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
  } else {
    setTimeout(() => {
      set('latVal', 'Unavailable');
      set('lonVal', 'Unavailable');
      set('accVal', 'N/A');
      set('timeVal', 'Unsupported');
      const label = document.getElementById('locationLabel');
      if (label) label.textContent = 'Geolocation unsupported';
      const sidebarAddress = document.getElementById('sidebarAddress');
      if (sidebarAddress) sidebarAddress.textContent = 'Geolocation unsupported';
      if (btn) btn.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i>';
      if (showToastOnSuccess) {
        showToast('Geolocation unsupported in your browser.', 'error');
      }
    }, 800);
  }
}

function startLiveLocationUpdates() {
  if (!navigator.geolocation) {
    showToast('Geolocation unsupported in your browser.', 'error');
    return;
  }
  setLiveLocationStatus(true);
  refreshLocation(false);
  if (liveLocationInterval) {
    clearInterval(liveLocationInterval);
  }
  liveLocationInterval = setInterval(() => {
      refreshLocation(false);
  }, 10000);
}

function initLiveLocationPage() {
  if (!document.getElementById('latVal')) return;

  const usesModernLiveLocationService = !!(
    window.DefenzaLiveLocationService &&
    typeof window.DefenzaLiveLocationService.startTracking === 'function' &&
    document.getElementById('liveLocationPage')
  );

  if (usesModernLiveLocationService) {
    console.debug('[Defenza Debug] Skipping legacy live-location initializer because the modern service is already active.');
    return;
  }

  fetchSavedLiveLocation();
  startLiveLocationUpdates();
}

const LOCATION_FRESHNESS_LIMIT_MS = 120000;

function shareLocation(event) {
  const domEvent = event ?? (typeof window !== 'undefined' && window.event ? window.event : null);
  const shareBtn = domEvent?.target?.closest('button');
  const performanceStart = Date.now();

  // Immediately show loading state and disable button
  if (shareBtn) {
    shareBtn.disabled = true;
    shareBtn.dataset.original = shareBtn.innerHTML;
    shareBtn.innerHTML = '<i class="fa-solid fa-spinner" style="animation: spin 1s linear infinite; margin-right: 0.35rem;"></i> Sharing...';
  }

  const showLoadingToast = () => {
    const existingLoader = document.getElementById('_shareLocationLoadingToast');
    if (!existingLoader) {
      showToast('📍 Sharing your live location... Please wait.', 'info', 0);
    }
  };

  showLoadingToast();

  const service = window.DefenzaLiveLocationService;
  const getStoredDefenzaLocation = () => {
    try {
      if (!window.localStorage) return null;
      const raw = window.localStorage.getItem('defenza_last_known_location');
      if (!raw) return null;
      const cached = JSON.parse(raw);
      if (!cached || !Number.isFinite(Number(cached.lat)) || !Number.isFinite(Number(cached.lon))) {
        return null;
      }
      const cachedTimestamp = Number(cached.timestamp || Date.now());
      const locationAgeMs = Date.now() - cachedTimestamp;
      if (!Number.isFinite(locationAgeMs) || locationAgeMs > LOCATION_FRESHNESS_LIMIT_MS) {
        return null;
      }
      return {
        lat: Number(cached.lat),
        lon: Number(cached.lon),
        acc: Number(cached.acc || 0),
        address: cached.address || 'Resolving address...',
        timestamp: cachedTimestamp,
        source: 'cached'
      };
    } catch (_) {
      return null;
    }
  };

  const getFastLocation = async () => {
    const freshLocation = await new Promise((resolve) => {
      if (!navigator.geolocation) {
        resolve(null);
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (position) => resolve({
          lat: position.coords.latitude,
          lon: position.coords.longitude,
          acc: position.coords.accuracy ?? 0,
          address: 'Resolving address...',
          timestamp: position.timestamp || Date.now(),
          source: 'gps'
        }),
        (error) => resolve(null),
        { enableHighAccuracy: true, maximumAge: 0, timeout: 10000 }
      );
    });

    if (freshLocation) {
      return freshLocation;
    }

    if (service?.lastPosition) {
      const locationAge = Date.now() - (service.lastPosition.timestamp || 0);
      const isAccurateLocation = service.isPositionAccurate(service.lastPosition);
      if (locationAge < LOCATION_FRESHNESS_LIMIT_MS && isAccurateLocation) {
        console.debug('[Defenza] Using in-memory location fallback (age:', locationAge, 'ms, acc:', service.lastPosition.acc, 'm)');
        return {
          lat: service.lastPosition.lat,
          lon: service.lastPosition.lon,
          acc: service.lastPosition.acc,
          address: service.lastPosition.address || 'Resolving address...',
          timestamp: service.lastPosition.timestamp,
          source: 'cached'
        };
      }
    }

    const storedLocation = getStoredDefenzaLocation();
    if (storedLocation) {
      console.debug('[Defenza] Using cached last known location from storage');
      return { ...storedLocation, source: 'cached' };
    }

    if (typeof window.showToast === 'function') {
      window.showToast('No recent location is available right now. Please try again when GPS is available.', 'warning');
    }

    return null;
  };

  const shareLocationHandler = async () => {
    try {
      const location = await getFastLocation();
      if (!location) {
        throw new Error('Unable to share location right now. Please try again in a moment.');
      }

      setLiveLocationStatus(true);

      const apiUrl = (document.getElementById('liveLocationPage')?.dataset.apiUrl || '/api/live-location.php').replace('/live-location.php', '/share-location.php');
      console.debug('[Defenza] Share Location API call', { location });
      
      const params = new URLSearchParams();
      params.append('lat', location.lat);
      params.append('lon', location.lon);
      params.append('acc', location.acc ?? 0);
      params.append('address', 'Resolving address...');
      params.append('status', 'active');
      params.append('timestamp', location.timestamp || Date.now());
      params.append('location_source', location.source || 'gps');

      const apiStart = Date.now();
      const resp = await fetch(apiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: params.toString()
      });
      const apiDuration = Date.now() - apiStart;
      console.debug('[Defenza] API response time: ' + apiDuration + 'ms');

      const data = await resp.json();
      if (!data.success || !data.email_sent) {
        throw new Error(data.email_error || data.message || 'Unable to share location');
      }

      updateLiveLocationUI(location.lat, location.lon, location.acc || 0, location.address || 'Current GPS Coordinates');
      
      const totalDuration = Date.now() - performanceStart;
      console.debug('[Defenza] Total share time: ' + totalDuration + 'ms');
      showToast(`📍 Shared with ${data.contacts_notified || 0} contact(s)`, 'success');
    } catch (error) {
      showToast(error.message || 'Failed to share location', 'error');
    } finally {
      // Re-enable button and restore original text
      if (shareBtn) {
        shareBtn.disabled = false;
        shareBtn.innerHTML = shareBtn.dataset.original || '<i class="fa-solid fa-location-arrow" style="margin-right:.35rem;"></i> Share My Location';
      }
    }
  };

  shareLocationHandler();
}

window.openCurrentLocationMap = function () {
  const service = window.DefenzaLiveLocationService;
  const location = service?.lastPosition;
  if (!location) {
    showToast('Location is still being fetched. Please wait a moment.', 'warning');
    return;
  }
  const url = `https://www.google.com/maps?q=${encodeURIComponent(location.lat)},${encodeURIComponent(location.lon)}`;
  window.open(url, '_blank', 'noopener,noreferrer');
};

// ── File upload preview ───────────────────────────────────
function updateUploadLabel(input) {
  if (input.files && input.files[0]) {
    const icon = document.getElementById('uploadIcon');
    const text = document.getElementById('uploadText');
    if (icon) icon.innerHTML = '<i class="fa-solid fa-circle-check" style="color:var(--purple-500);"></i>';
    if (text) text.innerHTML = `<div style="font-size:.875rem;font-weight:700;color:var(--purple-600);">${input.files[0].name}</div><div style="font-size:.72rem;color:#9ca3af;">${(input.files[0].size/1024).toFixed(0)} KB</div>`;
  }
}

// ── Avatar preview ────────────────────────────────────────
function initAvatarPreview() {
  document.getElementById('profileImg')?.addEventListener('change', function() {
    if (!this.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
      const wrap = document.querySelector('.avatar-upload-img');
      if (wrap) {
        wrap.style.background = 'none';
        wrap.innerHTML = `<img src="${e.target.result}" style="width:84px;height:84px;border-radius:50%;object-fit:cover;" />`;
      }
    };
    reader.readAsDataURL(this.files[0]);
  });
}

// ── Upload zone drag-drop ─────────────────────────────────
function initUploadZone() {
  const zone = document.getElementById('uploadZone');
  if (!zone) return;
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('dragover');
    const fi = document.getElementById('photoInput');
    if (fi && e.dataTransfer.files[0]) { fi.files = e.dataTransfer.files; updateUploadLabel(fi); }
  });
}

// ── Contact form demo ─────────────────────────────────────
function initContactFormDemo() {
  document.querySelectorAll('#addModal form, #editModal form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      demoContactSave();
    });
  });
  document.querySelectorAll('#deleteModal form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      closeModal('deleteModal');
      showToast('Contact removed.', 'success');
    });
  });
}

// ── DOMContentLoaded ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  const usesModernLiveLocationService = !!(
    window.DefenzaLiveLocationService &&
    typeof window.DefenzaLiveLocationService.startTracking === 'function' &&
    document.getElementById('liveLocationPage')
  );

  if (!usesModernLiveLocationService) {
    fetchUserLocationCoordinates();
  }

  initScrollAnimations();
  initFormValidation();
  initAuthForms();
  initDemoForms();
  initSOSDemo();
  if (!usesModernLiveLocationService) {
    initLiveLocationPage();
  }
  initAvatarPreview();
  initUploadZone();
  initContactFormDemo();

  // Animate stat counters
  document.querySelectorAll('.stat-value').forEach(el => {
    const num = parseInt(el.textContent.replace(/\D/g, ''));
    if (!isNaN(num) && num > 0 && num < 100000) animateCounter(el, num);
  });

  // Auto-hide flash alerts
  document.querySelectorAll('.alert-box').forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity .5s ease, transform .5s ease';
      alert.style.opacity    = '0';
      alert.style.transform  = 'translateY(-4px)';
      setTimeout(() => alert.remove(), 500);
    }, 5000);
  });

});
