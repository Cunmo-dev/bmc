// Load data from JSON files
let genres = [];
let movies = [];
let currentGenre = 'all';

// Load genres and movies
async function loadData() {
    try {
        const response = await fetch('./libMovie/genres.json');
        genres = await response.json();
        const moviesResponse = await fetch('./libMovie/movies.json', { cache: 'no-store' });
        movies = await moviesResponse.json();
        // Initialize UI
        renderGenres();
        renderMovies();
    } catch (error) {
        console.error('Error loading data:', error);
    }
}

// Render genres in sidebar
function renderGenres() {
    const genreList = document.getElementById('genreList');
    genreList.innerHTML = '';

    genres.forEach(genre => {
        const li = document.createElement('li');
        li.className = 'genre-item';
        
        const link = document.createElement('a');
        link.className = genre.id === 'all' ? 'genre-link active' : 'genre-link';
        link.dataset.genre = genre.id;
        link.innerHTML = `
            <span class="genre-icon">${genre.icon}</span>
            <span>${genre.name}</span>
        `;
        
        link.addEventListener('click', (e) => {
            e.preventDefault();
            filterByGenre(genre.id, genre.name, genre.icon);
        });
        
        li.appendChild(link);
        genreList.appendChild(li);
    });
}

// Render movies
function renderMovies(moviesToRender = movies) {
    const movieGrid = document.getElementById('movieGrid');
    movieGrid.innerHTML = '';

    moviesToRender.forEach(movie => {
        const genreObj = genres.find(g => g.id === movie.genre);
        const genreName = genreObj ? genreObj.name : movie.genre;
        
        const card = document.createElement('div');
        card.className = 'movie-card';
        card.dataset.genre = movie.genre;
        card.dataset.title = movie.title.toLowerCase();
        
        card.innerHTML = `
            <div class="movie-poster">${movie.icon}</div>
            <div class="play-button">▶</div>
            <div class="movie-info">
                <h3 class="movie-title">${movie.title}</h3>
                <div class="movie-meta">
                    <span>${movie.year} • ${genreName}</span>
                    <div class="rating">
                        <span>⭐</span>
                        <span>${movie.rating}</span>
                    </div>
                </div>
            </div>
        `;
        
        card.addEventListener('click', () => {
            openMovie(movie);
        });
        
        movieGrid.appendChild(card);
    });
}

// Filter movies by genre
function filterByGenre(genreId, genreName, genreIcon) {
    currentGenre = genreId;
    
    // Update active state
    document.querySelectorAll('.genre-link').forEach(link => {
        link.classList.remove('active');
    });
    document.querySelector(`[data-genre="${genreId}"]`).classList.add('active');
    
    // Filter movies
    const filteredMovies = genreId === 'all' 
        ? movies 
        : movies.filter(movie => movie.genre === genreId);
    
    renderMovies(filteredMovies);
    
    // Update section title
    const sectionTitle = document.querySelector('.section-title');
    if (genreId === 'all') {
        sectionTitle.textContent = '🔥 Phim Hot Nhất';
    } else {
        sectionTitle.textContent = `${genreIcon} Phim ${genreName}`;
    }
    
    // Scroll to movies section
    document.querySelector('#movies').scrollIntoView({ behavior: 'smooth' });
}

// Search functionality
function searchMovies(query) {
    query = query.toLowerCase().trim();
    
    if (!query) {
        // If search is empty, show all movies or current genre
        filterByGenre(currentGenre, '', '');
        return;
    }
    
    const filteredMovies = movies.filter(movie => 
        movie.title.toLowerCase().includes(query)
    );
    
    renderMovies(filteredMovies);
    
    // Update section title
    const sectionTitle = document.querySelector('.section-title');
    sectionTitle.textContent = `🔍 Kết quả tìm kiếm: "${query}"`;
    
    // Show message if no results
    if (filteredMovies.length === 0) {
        const movieGrid = document.getElementById('movieGrid');
        movieGrid.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #aaa;">
                <div style="font-size: 60px; margin-bottom: 20px;">😔</div>
                <h3 style="font-size: 24px; margin-bottom: 10px;">Không tìm thấy phim</h3>
                <p>Không có phim nào phù hợp với từ khóa "${query}"</p>
            </div>
        `;
    }
    
    // Scroll to movies section
    document.querySelector('#movies').scrollIntoView({ behavior: 'smooth' });
}

// Open movie detail
function openMovie(movie) {
    const movieUrl = `movie-detail.html?id=${movie.id}&title=${encodeURIComponent(movie.title)}`;
    alert(`Đang chuyển đến trang xem phim: ${movie.title}\n\nURL: ${movieUrl}\n\n(Trong ứng dụng thật, sẽ chuyển trang đến trang xem phim)`);
    // window.location.href = movieUrl; // Uncomment this in real app
}

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// Header scroll effect
window.addEventListener('scroll', () => {
    const header = document.querySelector('header');
    if (window.scrollY > 50) {
        header.style.background = 'rgba(10, 10, 10, 0.95)';
    } else {
        header.style.background = 'rgba(10, 10, 10, 0.8)';
    }
});

// Search event listeners
const searchInput = document.getElementById('searchInput');
const searchBtn = document.getElementById('searchBtn');

searchBtn.addEventListener('click', () => {
    searchMovies(searchInput.value);
});

searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        searchMovies(searchInput.value);
    }
});

// Real-time search (optional)
searchInput.addEventListener('input', () => {
    if (searchInput.value.length >= 2) {
        searchMovies(searchInput.value);
    } else if (searchInput.value.length === 0) {
        filterByGenre(currentGenre, '', '');
    }
});

// Initialize app
loadData();