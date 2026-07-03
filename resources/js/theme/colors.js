export const colors = {
    primary: '#2E7D32',
    secondary: '#00ACC1',
    accent: '#FFB300',
    background: '#F7F9FA',
    surface: '#FFFFFF',
    textPrimary: '#1C1C1C',
    textSecondary: '#6B7280',
    success: '#43A047',
    warning: '#FB8C00',
    error: '#E53935',
    fontanelle: '#1E88E5',
    instagram: '#8E24AA',
};

export const categoryMeta = {
    panorami: { icon: 'panorama', color: colors.primary, pastel: '#E8F5E9' },
    bagni: { icon: 'restroom', color: colors.secondary, pastel: '#E0F7FA' },
    fontanelle: { icon: 'fountain', color: colors.fontanelle, pastel: '#E3F2FD' },
    parcheggi: { icon: 'parking', color: colors.accent, pastel: '#FFF8E1' },
    'instagram-spot': { icon: 'camera', color: colors.instagram, pastel: '#F3E5F5' },
    panorama: { icon: 'panorama', color: colors.primary, pastel: '#E8F5E9' },
    restroom: { icon: 'restroom', color: colors.secondary, pastel: '#E0F7FA' },
    parking: { icon: 'parking', color: colors.accent, pastel: '#FFF8E1' },
    food: { icon: 'utensils', color: colors.warning, pastel: '#FFF3E0' },
};

export function chipStyle(slug, active) {
    const meta = categoryMeta[slug] ?? { color: colors.primary, pastel: '#E8F5E9' };

    if (active) {
        return {
            backgroundColor: meta.color,
            color: '#ffffff',
            boxShadow: `0 4px 14px ${meta.color}55`,
            border: `1.5px solid ${meta.color}`,
        };
    }

    return {
        backgroundColor: meta.pastel,
        color: meta.color,
        border: `1.5px solid ${meta.color}33`,
    };
}
