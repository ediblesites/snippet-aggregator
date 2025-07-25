let tileImages = [];
let board = [];
let emptyPos = { row: 3, col: 3 };
let lastMovePos = null;
let shuffleInterval;
let isMoving = false;

// Get the WordPress site URL for the API endpoint
function getApiUrl() {
    if (typeof wpApiSettings !== 'undefined' && wpApiSettings.root) {
        return wpApiSettings.root + 'puzzle/v1/images';
    }
    
    const protocol = window.location.protocol;
    const host = window.location.host;
    return 'https://orchsoldev.wpenginepowered.com/wp-json/puzzle/v1/images';
    //`${protocol}//${host}/wp-json/puzzle/v1/images`;
}

async function loadImages() {
    try {
        const response = await fetch(getApiUrl());
        const data = await response.json();
        
        if (data.success && data.images && data.images.length >= 15) {
            tileImages = data.images;
            initializePuzzle();
        } else {
            throw new Error('Invalid API response or insufficient images');
        }
    } catch (error) {
        console.error('Failed to load images from API:', error);
        document.getElementById('puzzleContainer').innerHTML = '<div class="puzzle-loading">Failed to load puzzle images. Please check your connection.</div>';
    }
}

function initializePuzzle() {
    board = Array.from({length: 4}, (_, row) => 
        Array.from({length: 4}, (_, col) => {
            const num = row * 4 + col + 1;
            return num === 16 ? 0 : num;
        })
    );
    emptyPos = { row: 3, col: 3 };
    lastMovePos = null;
    
    document.getElementById('puzzleContainer').innerHTML = '<div class="puzzle-grid" id="puzzleGrid"></div>';
    
    renderPuzzle();
    startAutoMovement();
}

function renderPuzzle() {
    const grid = document.getElementById('puzzleGrid');
    grid.innerHTML = '';

    board.flat().forEach((value, i) => {
        const row = Math.floor(i / 4);
        const col = i % 4;
        const tile = document.createElement('div');
        
        tile.className = `tile ${value === 0 ? 'empty' : ''} pos-${row}-${col}`;
        tile.dataset.row = row;
        tile.dataset.col = col;
        tile.dataset.value = value;
        
        if (value !== 0) {
            const img = document.createElement('img');
            img.src = tileImages[value - 1];
            img.alt = `Tile ${value}`;
            tile.appendChild(img);
            tile.onclick = () => moveTile(row, col);
        }

        grid.appendChild(tile);
    });
}

function isAdjacent(row, col) {
    const rowDiff = Math.abs(row - emptyPos.row);
    const colDiff = Math.abs(col - emptyPos.col);
    return (rowDiff === 1 && colDiff === 0) || (rowDiff === 0 && colDiff === 1);
}

function moveTile(row, col) {
    if (!isAdjacent(row, col) || isMoving) return false;
    
    isMoving = true;
    
    const emptyTile = document.querySelector(`[data-row="${emptyPos.row}"][data-col="${emptyPos.col}"]`);
    const clickedTile = document.querySelector(`[data-row="${row}"][data-col="${col}"]`);
    
    if (emptyTile && clickedTile) {
        emptyTile.className = emptyTile.className.replace(/pos-\d-\d/, `pos-${row}-${col}`);
        clickedTile.className = clickedTile.className.replace(/pos-\d-\d/, `pos-${emptyPos.row}-${emptyPos.col}`);
        
        setTimeout(() => {
            lastMovePos = { ...emptyPos };
            
            board[emptyPos.row][emptyPos.col] = board[row][col];
            board[row][col] = 0;
            
            emptyTile.dataset.row = row;
            emptyTile.dataset.col = col;
            clickedTile.dataset.row = emptyPos.row;
            clickedTile.dataset.col = emptyPos.col;
            
            emptyPos = { row, col };
            
            isMoving = false;
        }, 1200);
    }
    
    return true;
}

function getMovableTiles() {
    return Array.from({length: 4}, (_, row) => 
        Array.from({length: 4}, (_, col) => ({row, col}))
    )
    .flat()
    .filter(({row, col}) => 
        board[row][col] !== 0 && 
        isAdjacent(row, col) && 
        (!lastMovePos || !(row === lastMovePos.row && col === lastMovePos.col))
    );
}

function autoMove() {
    if (isMoving) return;
    
    const movableTiles = getMovableTiles();
    if (movableTiles.length > 0) {
        const randomTile = movableTiles[Math.floor(Math.random() * movableTiles.length)];
        moveTile(randomTile.row, randomTile.col);
    }
}

function startAutoMovement() {
    if (shuffleInterval) clearInterval(shuffleInterval);
    shuffleInterval = setInterval(autoMove, 100);
}

// Start loading images when DOM is ready
document.addEventListener('DOMContentLoaded', loadImages); 