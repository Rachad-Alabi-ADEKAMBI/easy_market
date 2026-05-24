const path = require('path');
const { app, BrowserWindow, Menu, shell } = require('electron');

const DEFAULT_START_URL = 'https://easy-market.xo.je/connexion';
const START_URL = process.env.EASYMARKET_DESKTOP_URL || DEFAULT_START_URL;

let mainWindow;

function iconPath() {
    if (app.isPackaged) {
        return path.join(process.resourcesPath, 'icons', 'logo.png');
    }

    return path.join(__dirname, '..', 'public', 'icons', 'logo.png');
}

function isInternalUrl(url) {
    try {
        return new URL(url).origin === new URL(START_URL).origin;
    } catch {
        return false;
    }
}

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1200,
        height: 820,
        minWidth: 980,
        minHeight: 640,
        title: 'EasyMarket',
        icon: iconPath(),
        backgroundColor: '#f7fbf9',
        webPreferences: {
            preload: path.join(__dirname, 'preload.cjs'),
            contextIsolation: true,
            nodeIntegration: false,
            sandbox: true,
        },
    });

    mainWindow.loadURL(START_URL);

    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        if (isInternalUrl(url)) {
            mainWindow.loadURL(url);
            return { action: 'deny' };
        }

        shell.openExternal(url);
        return { action: 'deny' };
    });

    mainWindow.webContents.on('will-navigate', (event, url) => {
        if (!isInternalUrl(url)) {
            event.preventDefault();
            shell.openExternal(url);
        }
    });

    mainWindow.webContents.on('did-fail-load', () => {
        mainWindow.loadFile(path.join(__dirname, 'offline.html'));
    });
}

app.whenReady().then(() => {
    Menu.setApplicationMenu(null);
    createWindow();

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) {
            createWindow();
        }
    });
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});
