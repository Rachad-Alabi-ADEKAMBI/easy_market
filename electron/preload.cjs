const { contextBridge } = require('electron');

contextBridge.exposeInMainWorld('easyMarketDesktop', {
    version: process.versions.electron,
});
