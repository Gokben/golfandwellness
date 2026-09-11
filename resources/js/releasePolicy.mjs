export function releaseChanged(loadedAsset, currentAsset) {
    return typeof loadedAsset === 'string' && typeof currentAsset === 'string'
        && /^app-[A-Za-z0-9_-]+\.js$/.test(loadedAsset)
        && /^app-[A-Za-z0-9_-]+\.js$/.test(currentAsset)
        && loadedAsset !== currentAsset;
}

export function canReload(openWindows, visible, dialogOpen) {
    return openWindows === 0 && visible && !dialogOpen;
}
