const R = 6371e3;

export function toRad(deg) {
    return (deg * Math.PI) / 180;
}

export function distanceMeters(lat1, lng1, lat2, lng2) {
    const φ1 = toRad(lat1);
    const φ2 = toRad(lat2);
    const Δφ = toRad(lat2 - lat1);
    const Δλ = toRad(lng2 - lng1);

    const a =
        Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
        Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}

export function formatDistance(meters) {
    if (meters < 1000) {
        return `${Math.round(meters)} m`;
    }

    return `${(meters / 1000).toFixed(1)} km`;
}

export function withDistance(pois, origin) {
    if (!origin) {
        return pois.map((poi) => ({ ...poi, distance_m: null, distance_label: null }));
    }

    return pois
        .map((poi) => {
            const distance_m = distanceMeters(
                origin.lat,
                origin.lng,
                Number(poi.latitude),
                Number(poi.longitude),
            );

            return {
                ...poi,
                distance_m,
                distance_label: formatDistance(distance_m),
            };
        })
        .sort((a, b) => a.distance_m - b.distance_m);
}
