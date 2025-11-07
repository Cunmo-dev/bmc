// Thêm API Key của bạn ở đây
const YOUTUBE_API_KEY = 'AIzaSyA3GCyKXbEx2pZNdDCkl8Aq5mdrb8iKwwU'; // Thay bằng API key thực của bạn
let categoriesConfig = null;
// Hàm lấy Channel ID từ handle
async function getChannelIdFromHandle(handle) {
    try {
        handle = handle.replace('@', '');
        const url = `https://www.googleapis.com/youtube/v3/channels?part=contentDetails,snippet&forHandle=${handle}&key=${YOUTUBE_API_KEY}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.items && data.items.length > 0) {
            return {
                channelId: data.items[0].id,
                uploadPlaylistId: data.items[0].contentDetails.relatedPlaylists.uploads,
                channelTitle: data.items[0].snippet.title
            };
        }
        throw new Error('Channel not found');
    } catch (error) {
        console.error('Error getting channel ID:', error);
        return null;
    }
}

// Hàm lấy tất cả playlists từ channel
async function getPlaylistsFromChannel(channelId) {
    try {
        const playlists = [];
        let nextPageToken = '';
        
        do {
            const url = `https://www.googleapis.com/youtube/v3/playlists?part=snippet,contentDetails&channelId=${channelId}&maxResults=50&key=${YOUTUBE_API_KEY}${nextPageToken ? '&pageToken=' + nextPageToken : ''}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.items) {
                data.items.forEach(item => {
                    playlists.push({
                        title: item.snippet.title,
                        contentId: item.id,
                        imageUrl: item.snippet.thumbnails?.high?.url || item.snippet.thumbnails?.default?.url,
                        videoCount: `${item.contentDetails.itemCount} videos`,
                        description: item.snippet.description
                    });
                });
            }
            
            nextPageToken = data.nextPageToken;
        } while (nextPageToken);
        
        console.log(`Loaded ${playlists.length} playlists from channel ${channelId}`);
        return playlists;
        
    } catch (error) {
        console.error('Error loading playlists:', error);
        return [];
    }
}

// Hàm lấy videos từ playlist bằng API
async function getPlaylistVideos(playlistId) {
    try {
        const videos = [];
        let nextPageToken = '';
        let index = 1;
        
        do {
            const url = `https://www.googleapis.com/youtube/v3/playlistItems?part=snippet,contentDetails&playlistId=${playlistId}&maxResults=200&key=${YOUTUBE_API_KEY}${nextPageToken ? '&pageToken=' + nextPageToken : ''}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (!data.items) {
                throw new Error('No items found in playlist');
            }
            
            // Lấy video IDs để fetch thêm thông tin duration
            const videoIds = data.items
                .filter(item => item.snippet.resourceId?.videoId)
                .map(item => item.snippet.resourceId.videoId)
                .join(',');
            
            // Fetch video details để lấy duration
            const detailsUrl = `https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id=${videoIds}&key=${YOUTUBE_API_KEY}`;
            const detailsResponse = await fetch(detailsUrl);
            const detailsData = await detailsResponse.json();
            
            // Tạo map duration theo videoId
            const durationMap = {};
            if (detailsData.items) {
                detailsData.items.forEach(video => {
                    durationMap[video.id] = formatDuration(video.contentDetails.duration);
                });
            }
            
            // Xử lý từng video
            data.items.forEach(item => {
                if (item.snippet.resourceId?.videoId) {
                    const videoId = item.snippet.resourceId.videoId;
                    
                    videos.push({
                        id: videoId,
                        title: item.snippet.title,
                        duration: durationMap[videoId] || 'N/A',
                        thumbnail: item.snippet.thumbnails?.high?.url || 
                                  item.snippet.thumbnails?.medium?.url || 
                                  item.snippet.thumbnails?.default?.url,
                        channelName: item.snippet.videoOwnerChannelTitle || item.snippet.channelTitle,
                        index: index++,
                        publishedAt: item.snippet.publishedAt
                    });
                }
            });
            
            nextPageToken = data.nextPageToken;
        } while (nextPageToken);
        
        console.log(`Loaded ${videos.length} videos from playlist ${playlistId}`);
        return videos;
        
    } catch (error) {
        console.error('Error getting playlist videos:', error);
        // Fallback to mock data for testing
        return generateMockVideos();
    }
}

// Hàm chuyển đổi ISO 8601 duration sang format dễ đọc
function formatDuration(isoDuration) {
    const match = isoDuration.match(/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/);
    
    if (!match) return 'N/A';
    
    const hours = parseInt(match[1]) || 0;
    const minutes = parseInt(match[2]) || 0;
    const seconds = parseInt(match[3]) || 0;
    
    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    } else {
        return `${minutes}:${String(seconds).padStart(2, '0')}`;
    }
}

// Include playlist extraction functions
function extractPlaylists(ytInitialData) {
    try {
        // Kiểm tra xem ytInitialData có tồn tại không
        if (!ytInitialData || !ytInitialData.contents) {
            console.warn('ytInitialData không hợp lệ hoặc không có dữ liệu contents');
            return [];
        }

        // Điều hướng đến phần chứa playlists
        const twoColumnBrowseResults = ytInitialData.contents.twoColumnBrowseResultsRenderer;
        if (!twoColumnBrowseResults || !twoColumnBrowseResults.tabs) {
            console.warn('Không tìm thấy twoColumnBrowseResultsRenderer hoặc tabs');
            return [];
        }

        // Tìm tab "Playlists"
        const playlistTab = twoColumnBrowseResults.tabs.find(tab =>
            tab.tabRenderer && (tab.tabRenderer.title === 'Playlists' || tab.tabRenderer.title === 'Danh sách phát')
        );

        if (!playlistTab || !playlistTab.tabRenderer.content) {
            console.warn('Không tìm thấy tab Playlists hoặc content');
            return [];
        }

        // Điều hướng đến gridRenderer
        const sectionList = playlistTab.tabRenderer.content.sectionListRenderer;
        if (!sectionList || !sectionList.contents || !sectionList.contents[0]) {
            console.warn('Không tìm thấy sectionListRenderer hoặc contents');
            return [];
        }

        const itemSection = sectionList.contents[0].itemSectionRenderer;
        if (!itemSection || !itemSection.contents || !itemSection.contents[0]) {
            console.warn('Không tìm thấy itemSectionRenderer');
            return [];
        }

        const gridRenderer = itemSection.contents[0].gridRenderer;
        if (!gridRenderer || !gridRenderer.items) {
            console.warn('Không tìm thấy gridRenderer hoặc items');
            return [];
        }

        // Trích xuất thông tin từ mỗi playlist
        const playlists = [];

        gridRenderer.items.forEach((item, index) => {
            try {
                const lockupViewModel = item.lockupViewModel;
                if (!lockupViewModel) {
                    console.warn(`Item ${index}: Không tìm thấy lockupViewModel`);
                    return;
                }

                // Lấy title
                const title = lockupViewModel.metadata?.lockupMetadataViewModel?.title?.content || 'Không có tiêu đề';

                // Lấy contentId
                const contentId = lockupViewModel.contentId || 'Không có contentId';

                // Lấy URL ảnh với nhiều cách khác nhau
                let imageUrl = null;
                const contentImage = lockupViewModel.contentImage;

                // Cách 1: Từ collectionThumbnailViewModel
                if (contentImage &&
                    contentImage.collectionThumbnailViewModel &&
                    contentImage.collectionThumbnailViewModel.primaryThumbnail &&
                    contentImage.collectionThumbnailViewModel.primaryThumbnail.thumbnailViewModel &&
                    contentImage.collectionThumbnailViewModel.primaryThumbnail.thumbnailViewModel.image &&
                    contentImage.collectionThumbnailViewModel.primaryThumbnail.thumbnailViewModel.image.sources &&
                    contentImage.collectionThumbnailViewModel.primaryThumbnail.thumbnailViewModel.image.sources[0]) {

                    imageUrl = contentImage.collectionThumbnailViewModel.primaryThumbnail.thumbnailViewModel.image.sources[0].url;
                }

                // Cách 2: Nếu không có, thử từ thumbnailViewModel trực tiếp
                if (!imageUrl && contentImage && contentImage.thumbnailViewModel &&
                    contentImage.thumbnailViewModel.image && contentImage.thumbnailViewModel.image.sources &&
                    contentImage.thumbnailViewModel.image.sources[0]) {
                    imageUrl = contentImage.thumbnailViewModel.image.sources[0].url;
                }

                // Thêm thông tin bổ sung (số video)
                let videoCount = 'Không rõ';

                // Cách 1: Từ overlays
                const overlays = contentImage?.collectionThumbnailViewModel?.primaryThumbnail?.thumbnailViewModel?.overlays;
                if (overlays && overlays[0] && overlays[0].thumbnailOverlayBadgeViewModel) {
                    const badge = overlays[0].thumbnailOverlayBadgeViewModel.thumbnailBadges?.[0]?.thumbnailBadgeViewModel;
                    if (badge && badge.text) {
                        videoCount = badge.text;
                    }
                }

                // Cách 2: Từ metadata
                if (videoCount === 'Không rõ') {
                    const metadata = lockupViewModel.metadata?.lockupMetadataViewModel?.metadata;
                    if (metadata && metadata.metadataRowViewModel && metadata.metadataRowViewModel.metadataParts) {
                        const videoPart = metadata.metadataRowViewModel.metadataParts.find(part =>
                            part.text && part.text.content && part.text.content.includes('video')
                        );
                        if (videoPart) {
                            videoCount = videoPart.text.content;
                        }
                    }
                }

                playlists.push({
                    title: title,
                    contentId: contentId,
                    imageUrl: imageUrl,
                    videoCount: videoCount
                });

            } catch (error) {
                console.warn(`Lỗi khi xử lý item ${index}:`, error);
            }
        });

        return playlists;

    } catch (error) {
        console.error('Lỗi khi trích xuất playlists:', error);
        return [];
    }
}

// Hàm để trích xuất ytInitialData từ HTML content
function extractYtInitialDataFromHtml(html) {
    try {
        // Danh sách các pattern để tìm ytInitialData
        const patterns = [
            /var ytInitialData\s*=\s*({.+?});/s,
            /window\["ytInitialData"\]\s*=\s*({.+?});/s,
            /window\.ytInitialData\s*=\s*({.+?});/s,
            /"ytInitialData":({.+?}),"ytInitialPlayerResponse"/s,
            /ytInitialData["']?\s*[:=]\s*({.+?});/s
        ];

        for (const pattern of patterns) {
            const match = html.match(pattern);
            if (match && match[1]) {
                try {
                    const ytInitialData = JSON.parse(match[1]);
                    return ytInitialData;
                } catch (parseError) {
                    console.warn('Lỗi parse JSON với pattern:', pattern);
                    continue;
                }
            }
        }

        throw new Error('Không tìm thấy ytInitialData trong HTML với bất kỳ pattern nào');

    } catch (error) {
        console.error('Lỗi khi trích xuất ytInitialData:', error);
        throw error;
    }
}

// Sample movie data (fallback data)
const fallbackMovies = [];
let currentCategory = 'all';
let allPlaylists = []; // Lưu trữ tất cả playlists đã tải
let playlistsByCategory = {}; // Lưu trữ playlists theo từng category

// Load movies on page load
document.addEventListener('DOMContentLoaded', async function () {
    setupSearch();
    setupModal();
    
    showLoading();
    
    // Load categories config first
    await loadCategoriesConfig();
    
    // Render categories in sidebar
    renderCategoriesSidebar();
    
    if (fallbackMovies.length > 0) {
        loadMovies(fallbackMovies);
    }
    
    loadAllCategoryPlaylistsProgressively();
});

function renderCategoriesSidebar() {
    const categoryList = document.querySelector('.category-list');
    categoryList.innerHTML = '';
    
    categoriesConfig.forEach(category => {
        const categoryItem = document.createElement('div');
        categoryItem.className = 'category-item' + (category.id === 'all' ? ' active' : '');
        categoryItem.onclick = () => filterByGenre(category.id);
        
        categoryItem.innerHTML = `
            <div class="category-icon">${category.icon}</div>
            <span>${category.name}</span>
        `;
        
        categoryList.appendChild(categoryItem);
    });
}

async function loadAllCategoryPlaylistsProgressively() {
    const categories = categoriesConfig
        .filter(cat => cat.id !== 'all' && cat.url)
        .map(cat => cat.id);

    // Hiển thị loading với progress
    showProgressiveLoading(categories.length);

    let completedCount = 0;
    let hasDisplayedAny = false;

    // Load từng category song song nhưng hiển thị ngay khi xong
    const loadPromises = categories.map(async (category, index) => {
        if (!getCategoryUrl(category)) {
            playlistsByCategory[category] = [];
            return;
        }

        try {
            console.log(`Loading playlists for ${category}...`);

            // Delay để tránh spam requests
            await new Promise(resolve => setTimeout(resolve, index * 500));

            const playlists = await loadPlaylistsFromURL(getCategoryUrl(category));
            const movieData = convertPlaylistsToMovies(playlists, category);
            playlistsByCategory[category] = movieData;

            // Thêm vào allPlaylists
            allPlaylists = allPlaylists.concat(movieData);

            console.log(`Loaded ${playlists.length} playlists for ${category}`);

            // Cập nhật progress
            completedCount++;
            updateProgressiveLoading(completedCount, categories.length, category);

            // Nếu đang ở tab "Tất cả" thì hiển thị ngay
            if (currentCategory === 'all') {
                displayAllMoviesProgressive();
                hasDisplayedAny = true;
            }

        } catch (error) {
            console.error(`Error loading playlists for ${category}:`, error);
            playlistsByCategory[category] = [];
            completedCount++;
            updateProgressiveLoading(completedCount, categories.length, category, true);
        }
    });

    // Đợi tất cả load xong
    await Promise.all(loadPromises);

    // Nếu chưa hiển thị gì (không có fallback data), hiển thị tất cả
    if (!hasDisplayedAny) {
        displayAllMoviesProgressive();
    }

    // Ẩn progress loading
    hideProgressiveLoading();
    console.log('All playlists loaded:', allPlaylists);
}
function getCategoryUrl(categoryId) {
    const category = categoriesConfig.find(cat => cat.id === categoryId);
    return category ? category.url : null;
}

function getCategoryName(categoryId) {
    const category = categoriesConfig.find(cat => cat.id === categoryId);
    return category ? category.name : categoryId;
}
function hideProgressiveLoading() {
    setTimeout(() => {
        const progressContainer = document.getElementById('progressContainer');
        if (progressContainer) {
            progressContainer.style.opacity = '0';
            progressContainer.style.transform = 'translateX(100%)';
            progressContainer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';

            setTimeout(() => {
                progressContainer.remove();
            }, 500);
        }
    }, 2000); // Hiển thị thêm 2s rồi mới ẩn
}

function getCategoryDisplayNames() {
    const names = {};
    categoriesConfig.forEach(cat => {
        names[cat.id] = cat.name;
    });
    return names;
}
function updateProgressiveLoading(completed, total, categoryName, hasError = false) {
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    const currentCategory = document.getElementById('currentCategory');

    if (progressFill && progressText && currentCategory) {
        const percentage = (completed / total) * 100;
        progressFill.style.width = percentage + '%';
        progressText.textContent = `${completed}/${total} categories loaded`;

        const categoryDisplayNames = getCategoryDisplayNames();

        if (hasError) {
            currentCategory.textContent = `❌ Lỗi: ${categoryDisplayNames[categoryName] || categoryName}`;
            currentCategory.style.color = '#ff4444';
        } else {
            currentCategory.textContent = `✅ Hoàn thành: ${categoryDisplayNames[categoryName] || categoryName}`;
            currentCategory.style.color = '#44ff44';
        }

        // Nếu đã xong hết, hiển thị thông báo hoàn thành
        if (completed === total) {
            setTimeout(() => {
                currentCategory.textContent = '🎉 Tải xong tất cả playlists!';
                currentCategory.style.color = '#ff6b35';
            }, 500);
        }
    }
}
function displayAllMoviesProgressive() {
    const allMovies = [...fallbackMovies, ...allPlaylists];

    if (allMovies.length === 0) {
        return;
    }

    // Nếu đã có movies hiển thị, chỉ thêm những cái mới
    const moviesGrid = document.getElementById('moviesGrid');
    const currentMovieIds = Array.from(moviesGrid.children).map(card =>
        card.getAttribute('data-movie-id')
    ).filter(id => id); // Lọc bỏ null/undefined

    // Chỉ thêm những movies chưa được hiển thị
    const newMovies = allMovies.filter(movie =>
        !currentMovieIds.includes(movie.id)
    );

    // Thêm từng movie card với animation
    newMovies.forEach((movie, index) => {
        setTimeout(() => {
            addMovieCardAnimated(movie);
        }, index * 100); // Delay 100ms giữa mỗi card
    });
}
function addMovieCardAnimated(movie) {
    const moviesGrid = document.getElementById('moviesGrid');

    // Xóa loading message nếu có
    const loadingEl = moviesGrid.querySelector('.loading-message');
    if (loadingEl) {
        loadingEl.remove();
    }

    const movieCard = createMovieCard(movie);
    movieCard.setAttribute('data-movie-id', movie.id);
    movieCard.style.opacity = '0';
    movieCard.style.transform = 'translateY(20px)';
    movieCard.style.transition = 'opacity 0.5s ease, transform 0.5s ease';

    moviesGrid.appendChild(movieCard);

    // Trigger animation
    setTimeout(() => {
        movieCard.style.opacity = '1';
        movieCard.style.transform = 'translateY(0)';
    }, 50);
}
function showProgressiveLoading(total) {
    const moviesGrid = document.getElementById('moviesGrid');

    // Nếu đã có fallback movies thì không xóa, chỉ thêm progress
    if (fallbackMovies.length === 0) {
        moviesGrid.innerHTML = '';
    }

    const progressContainer = document.createElement('div');
    progressContainer.id = 'progressContainer';
    progressContainer.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: rgba(0,0,0,0.8);
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        z-index: 1000;
        min-width: 250px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    `;

    progressContainer.innerHTML = `
        <div style="margin-bottom: 10px;">
            <strong>Đang tải playlists...</strong>
        </div>
        <div id="progressBar" style="background: #333; height: 6px; border-radius: 3px; overflow: hidden;">
            <div id="progressFill" style="background: linear-gradient(90deg, #ff6b35, #f7931e); height: 100%; width: 0%; transition: width 0.3s ease;"></div>
        </div>
        <div id="progressText" style="margin-top: 8px; font-size: 0.9em; color: #ccc;">
            0/${total} categories loaded
        </div>
        <div id="currentCategory" style="margin-top: 5px; font-size: 0.8em; color: #ff6b35;">
            Initializing...
        </div>
    `;

    document.body.appendChild(progressContainer);
}

// Hàm load playlists cho tất cả categories
async function loadAllCategoryPlaylists() {
    const categories = ['action', 'comedy', 'drama', 'horror', 'romance', 'sci-fi', 'animation'];

    // Load playlists song song cho tất cả categories
    const promises = categories.map(async (category) => {
        if (getCategoryUrl(category)) {
            try {
                console.log(`Loading playlists for ${category}...`);
                const playlists = await loadPlaylistsFromURL(getCategoryUrl(category));
                const movieData = convertPlaylistsToMovies(playlists, category);
                playlistsByCategory[category] = movieData;

                // Thêm vào allPlaylists
                allPlaylists = allPlaylists.concat(movieData);

                console.log(`Loaded ${playlists.length} playlists for ${category}`);
                return movieData;
            } catch (error) {
                console.error(`Error loading playlists for ${category}:`, error);
                playlistsByCategory[category] = [];
                return [];
            }
        } else {
            playlistsByCategory[category] = [];
            return [];
        }
    });

    // Đợi tất cả categories load xong
    await Promise.all(promises);

    console.log('All playlists loaded:', allPlaylists);
}

// Hàm hiển thị tất cả movies (fallback + playlists)
function displayAllMovies() {
    const allMovies = [...fallbackMovies, ...allPlaylists];
    loadMovies(allMovies);
}


async function loadPlaylistsFromURL(url) {
    try {
        // Trích xuất handle từ URL
        const handleMatch = url.match(/@([^\/]+)/);
        if (!handleMatch) {
            throw new Error('Invalid YouTube URL format');
        }
        
        const handle = handleMatch[1];
        console.log(`Extracting playlists from handle: ${handle}`);
        
        // Lấy channel ID từ handle
        const channelInfo = await getChannelIdFromHandle(handle);
        if (!channelInfo) {
            throw new Error('Could not get channel info');
        }
        
        console.log(`Found channel: ${channelInfo.channelTitle} (${channelInfo.channelId})`);
        
        // Lấy tất cả playlists từ channel
        const playlists = await getPlaylistsFromChannel(channelInfo.channelId);
        
        return playlists;
        
    } catch (error) {
        console.error('Error loading playlists from URL:', error);
        console.log('Falling back to mock playlists');
        return generateMockPlaylists();
    }
}

// Hàm tạo mock playlists để test giao diện
function generateMockPlaylists() {
    return [
        {
            title: "Best Action Movies 2024",
            contentId: "PLrAl9cZR2PkN1234567890",
            imageUrl: null,
            videoCount: "25 videos"
        },
        {
            title: "Comedy Collection",
            contentId: "PLrAl9cZR2PkN0987654321",
            imageUrl: null,
            videoCount: "18 videos"
        },
        {
            title: "Horror Masterpieces",
            contentId: "PLrAl9cZR2PkN1122334455",
            imageUrl: null,
            videoCount: "32 videos"
        }
    ];
}
async function loadCategoriesConfig() {
    try {
        const response = await fetch('./libMovie/categories.json');
        const data = await response.json();
        categoriesConfig = data.categories;
        return categoriesConfig;
    } catch (error) {
        console.error('Error loading categories config:', error);
        // Fallback to default if JSON load fails
        return getDefaultCategories();
    }
}
// Hàm chuyển đổi playlists thành format movies
function convertPlaylistsToMovies(playlists, category) {
    return playlists.map((playlist, index) => {
        // Tạo rating ngẫu nhiên từ 4.0 đến 5.0
        const rating = (4.0 + Math.random()).toFixed(1);

        // Map category to Vietnamese
        const categoryNames = {
            'action': 'Hành Động',
            'comedy': 'Hài Hước',
            'drama': 'Chính Kịch',
            'horror': 'Kinh Dị',
            'romance': 'Lãng Mạn',
            'sci-fi': 'Khoa Học Viễn Tưởng',
            'animation': 'Hoạt Hình'
        };

        return {
            id: `${category}_${index}`,
            title: playlist.title,
            genre: categoryNames[category] || 'Phim',
            rating: parseFloat(rating),
            year: new Date().getFullYear(), // Current year as placeholder
            categories: [category],
            description: `Playlist ${playlist.title} với ${playlist.videoCount} video. Đây là một bộ sưu tập phim ${categoryNames[category].toLowerCase()} được tuyển chọn.`,
            imageUrl: playlist.imageUrl,
            contentId: playlist.contentId,
            videoCount: playlist.videoCount,
            isPlaylist: true
        };
    });
}

function loadMovies(movieList) {
    const moviesGrid = document.getElementById('moviesGrid');
    moviesGrid.innerHTML = '';

    if (movieList.length === 0) {
        moviesGrid.innerHTML = '<div style="text-align: center; padding: 2rem; color: rgba(255,255,255,0.7);"><p>Không tìm thấy phim nào phù hợp</p></div>';
        return;
    }

    movieList.forEach((movie, index) => {
        const movieCard = createMovieCard(movie);
        movieCard.style.animationDelay = `${index * 0.1}s`;
        moviesGrid.appendChild(movieCard);
    });
}

function createMovieCard(movie) {
    const card = document.createElement('div');
    card.className = 'movie-card';
    card.onclick = () => showMovieDetails(movie);

    const stars = '⭐'.repeat(Math.floor(movie.rating));

    // Sử dụng ảnh từ YouTube nếu có, otherwise sử dụng placeholder
    const posterContent = movie.imageUrl
    ? `<img src="${movie.imageUrl}" alt="${movie.title}" style="width: 100%; height: 100%; object-fit: cover; object-position: center; display: block;">`
    : '<div style="font-size: 4rem; opacity: 0.5;">🎬</div>';

    card.innerHTML = `
        <div class="movie-poster">${posterContent}</div>
        <div class="movie-info">
            <h3 class="movie-title">${movie.title}</h3>
            <p class="movie-genre">${movie.genre} • ${movie.year}</p>
            <div class="movie-rating">
                <span class="stars">${stars}</span>
                <span>${movie.rating}/5</span>
                ${movie.isPlaylist ? `<span style="margin-left: 10px; color: #ff6b35;">📋 ${movie.videoCount}</span>` : ''}
            </div>
        </div>
    `;

    return card;
}



function setupModal() {
    const modal = document.getElementById('movieModal');
    const closeBtn = document.querySelector('.close');

    closeBtn.onclick = function () {
        modal.style.display = 'none';
    }

    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
}


async function filterByGenre(genre) {
    currentCategory = genre;

    // ** THÊM ĐOẠN NÀY: Nếu đang ở playlist view, quay lại movies view trước **
    if (isPlaylistView) {
        backToMovies();
        // Đợi DOM được render lại
        await new Promise(resolve => setTimeout(resolve, 100));
    }

    // Update active category
    document.querySelectorAll('.category-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // ** SỬA DÒNG NÀY để tránh lỗi khi event không có target **
    const targetElement = event?.target?.closest('.category-item');
    if (targetElement) {
        targetElement.classList.add('active');
    }

    // Filter movies
    let filteredMovies = [];

    if (genre === 'all') {
        filteredMovies = [...fallbackMovies, ...allPlaylists];
    } else {
        const playlistMovies = playlistsByCategory[genre] || [];
        const fallbackOfCategory = fallbackMovies.filter(movie =>
            movie.categories.includes(genre)
        );
        filteredMovies = [...playlistMovies, ...fallbackOfCategory];
    }

    // Apply search filter if there's text in search box
    const searchQuery = document.getElementById('searchInput').value.toLowerCase();
    if (searchQuery) {
        filteredMovies = filteredMovies.filter(movie =>
            movie.title.toLowerCase().includes(searchQuery) ||
            movie.genre.toLowerCase().includes(searchQuery)
        );
    }

    // Load movies immediately
    loadMovies(filteredMovies);

    // Update section title
    const sectionTitle = document.querySelector('.section-title');
    if (sectionTitle) { // ** THÊM CHECK NULL **
        if (genre === 'all') {
            sectionTitle.textContent = 'Phim Nổi Bật';
        } else {
            const categoryNames = getCategoryDisplayNames(); // ** SỬA NẾU BẠN ĐÃ ÁP DỤNG JSON CONFIG **
            sectionTitle.textContent = categoryNames[genre] || 'Phim Nổi Bật';
        }
    }
}

// Add loading animation for movie cards
function showLoading() {
    const moviesGrid = document.getElementById('moviesGrid');
    moviesGrid.innerHTML = '<div style="text-align: center; padding: 2rem;"><div class="loading"></div><p style="margin-top: 1rem;">Đang tải phim...</p></div>';
}


// Biến global để lưu trạng thái
let currentPlaylistId = null;
let currentPlaylistVideos = [];
let isPlaylistView = false;

// Hàm xem playlist - thay thế cho window.open trong showMovieDetails
async function viewPlaylist(movie) {
    try {
        // Hiển thị loading
        showPlaylistLoading(movie.title);

        // Lấy danh sách video từ playlist
        const videos = await getPlaylistVideos(movie.contentId);

        if (videos.length === 0) {
            alert('Không thể tải danh sách video từ playlist này');
            return;
        }

        // Lưu trạng thái
        currentPlaylistId = movie.contentId;
        currentPlaylistVideos = videos;
        isPlaylistView = true;

        // Hiển thị playlist
        displayPlaylistView(movie, videos);

        // Đóng modal
        document.getElementById('movieModal').style.display = 'none';

    } catch (error) {
        console.error('Error loading playlist:', error);
        alert('Có lỗi khi tải playlist. Vui lòng thử lại!');
    }
}


// Hàm trích xuất videos từ ytInitialData
function extractVideosFromPlaylist(ytInitialData) {
    try {
        const videos = [];

        // Điều hướng đến playlist content theo structure thực tế
        const contents = ytInitialData?.contents?.twoColumnBrowseResultsRenderer?.tabs;

        if (!contents || !Array.isArray(contents)) {
            console.warn('Không tìm thấy tabs trong ytInitialData');
            return [];
        }

        // Tìm tab được chọn (selected: true)
        const selectedTab = contents.find(tab => tab.tabRenderer?.selected);
        if (!selectedTab) {
            console.warn('Không tìm thấy tab được chọn');
            return [];
        }

        // Điều hướng đến sectionListRenderer
        const sectionList = selectedTab.tabRenderer?.content?.sectionListRenderer?.contents;
        if (!sectionList || !Array.isArray(sectionList)) {
            console.warn('Không tìm thấy sectionListRenderer contents');
            return [];
        }

        // Tìm itemSectionRenderer
        const itemSection = sectionList.find(section => section.itemSectionRenderer);
        if (!itemSection) {
            console.warn('Không tìm thấy itemSectionRenderer');
            return [];
        }

        // Tìm playlistVideoListRenderer
        const playlistVideoList = itemSection.itemSectionRenderer?.contents?.find(
            content => content.playlistVideoListRenderer
        );

        if (!playlistVideoList) {
            console.warn('Không tìm thấy playlistVideoListRenderer');
            return [];
        }

        const videoContents = playlistVideoList.playlistVideoListRenderer?.contents;
        if (!videoContents || !Array.isArray(videoContents)) {
            console.warn('Không tìm thấy video contents');
            return [];
        }

        // Trích xuất thông tin từng video
        videoContents.forEach((item, index) => {
            try {
                const videoRenderer = item.playlistVideoRenderer;
                if (!videoRenderer) return;

                // Lấy thông tin video
                const title = videoRenderer.title?.runs?.[0]?.text || 'Không có tiêu đề';
                const videoId = videoRenderer.videoId || '';
                const duration = videoRenderer.lengthText?.simpleText || 'N/A';

                // Lấy thumbnail - chọn thumbnail có độ phân giải tốt nhất
                let thumbnail = '';
                if (videoRenderer.thumbnail?.thumbnails && Array.isArray(videoRenderer.thumbnail.thumbnails)) {
                    const thumbnails = videoRenderer.thumbnail.thumbnails;
                    // Lấy thumbnail cuối cùng (thường có độ phân giải cao nhất)
                    thumbnail = thumbnails[thumbnails.length - 1]?.url || thumbnails[0]?.url || '';
                }

                // Lấy thông tin kênh
                const channelName = videoRenderer.shortBylineText?.runs?.[0]?.text || 'Không rõ';

                // Lấy số thứ tự
                const indexNumber = videoRenderer.index?.simpleText || (index + 1).toString();

                // Lấy thông tin view và thời gian upload
                let viewInfo = '';
                if (videoRenderer.videoInfo?.runs && Array.isArray(videoRenderer.videoInfo.runs)) {
                    viewInfo = videoRenderer.videoInfo.runs.map(run => run.text).join('');
                }

                videos.push({
                    id: videoId,
                    title: title,
                    duration: duration,
                    thumbnail: thumbnail,
                    channelName: channelName,
                    index: parseInt(indexNumber) || (index + 1),
                    viewInfo: viewInfo
                });

            } catch (error) {
                console.warn(`Lỗi khi xử lý video ${index}:`, error);
            }
        });

        console.log(`Đã trích xuất được ${videos.length} videos từ playlist`);
        return videos;

    } catch (error) {
        console.error('Lỗi khi trích xuất videos:', error);
        return [];
    }
}

// Mock videos cho testing
function generateMockVideos() {
    const mockVideos = [];
    for (let i = 1; i <= 10; i++) {
        mockVideos.push({
            id: `mock_video_${i}`,
            title: `Video số ${i} - Phim hay nhất ${new Date().getFullYear()}`,
            duration: `${Math.floor(Math.random() * 60) + 60}:${String(Math.floor(Math.random() * 60)).padStart(2, '0')}`,
            thumbnail: `https://img.youtube.com/vi/dQw4w9WgXcQ/mqdefault.jpg`,
            channelName: 'Kênh phim hay',
            index: i
        });
    }
    return mockVideos;
}

// Hiển thị loading khi tải playlist
function showPlaylistLoading(playlistTitle) {
    const moviesSection = document.getElementById('movies');

    moviesSection.innerHTML = `
        <div style="display: flex; align-items: center; margin-bottom: 2rem;">
            <button onclick="backToMovies()" style="background: transparent; border: 2px solid #ff6b35; color: #ff6b35; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer; margin-right: 1rem; font-size: 1rem;">
                ← Quay lại
            </button>
            <h2 class="section-title">Đang tải: ${playlistTitle}</h2>
        </div>
        <div style="text-align: center; padding: 3rem;">
            <div class="loading"></div>
            <p style="margin-top: 1rem; color: rgba(255,255,255,0.7);">Đang tải danh sách video...</p>
        </div>
    `;
}

// Hiển thị playlist view
function displayPlaylistView(movie, videos) {
    const moviesSection = document.getElementById('movies');

    const videosHtml = videos.map((video, index) => `
        <div class="movie-card" onclick="playVideo('${video.id}', '${video.title.replace(/'/g, "\\'")}')">
            <div class="movie-poster">
                ${video.thumbnail ?
            `<img src="${video.thumbnail}" alt="${video.title}" style="width: 100%; height: 100%; object-fit: cover;">` :
            '<div style="font-size: 4rem; opacity: 0.5;">🎬</div>'
        }
                <div style="position: absolute; bottom: 5px; right: 5px; background: rgba(0,0,0,0.8); color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem;">
                    ${video.duration}
                </div>
                <div style="position: absolute; top: 5px; left: 5px; background: #ff6b35; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; font-weight: bold;">
                    ${video.index}
                </div>
            </div>
            <div class="movie-info">
                <h3 class="movie-title" style="font-size: 0.9rem; line-height: 1.3;">${video.title}</h3>
                <p class="movie-genre" style="font-size: 0.8rem; opacity: 0.8;">${video.channelName}</p>
                <div class="movie-rating">
                    <span style="color: #ff6b35; font-size: 0.8rem;">▶ Xem ngay</span>
                </div>
            </div>
        </div>
    `).join('');

    moviesSection.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center;">
                <button onclick="backToMovies()" style="background: transparent; border: 2px solid #ff6b35; color: #ff6b35; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer; margin-right: 1rem; font-size: 1rem; transition: all 0.3s ease;">
                    ← Quay lại
                </button>
                <div>
                    <h2 class="section-title" style="margin: 0;">${movie.title}</h2>
                    <p style="color: rgba(255,255,255,0.7); margin: 0.5rem 0 0 0; font-size: 0.9rem;">${videos.length} video • ${movie.genre}</p>
                </div>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <button onclick="playAllVideos()" style="background: linear-gradient(45deg, #ff6b35, #f7931e); color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 25px; cursor: pointer; font-size: 0.9rem; font-weight: bold;">
                    ▶ Phát tất cả
                </button>
                <button onclick="shufflePlaylist()" style="background: transparent; border: 2px solid #ff6b35; color: #ff6b35; padding: 0.8rem 1.5rem; border-radius: 25px; cursor: pointer; font-size: 0.9rem;">
                    🔀 Phát ngẫu nhiên
                </button>
            </div>
        </div>
        <div class="movies-grid" id="videosGrid">
            ${videosHtml}
        </div>
    `;

    // Thêm animation cho video cards
    const videoCards = document.querySelectorAll('#videosGrid .movie-card');
    videoCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 50);
    });
}

// Hàm quay lại danh sách phim
function backToMovies() {
    isPlaylistView = false;
    currentPlaylistId = null;
    currentPlaylistVideos = [];

    // Khôi phục lại movies view
    const moviesSection = document.getElementById('movies');
    moviesSection.innerHTML = `
        <h2 class="section-title">Phim Nổi Bật</h2>
        <div class="movies-grid" id="moviesGrid">
            <!-- Movies will be loaded here -->
        </div>
    `;

    // Load lại movies dựa trên category hiện tại
    if (currentCategory === 'all') {
        displayAllMoviesProgressive();
    } else {
        filterByGenre(currentCategory);
    }
}

// Hàm phát video
function playVideo(videoId, videoTitle) {
    showVideoModal(videoId, videoTitle);
}


// Hàm phát tất cả video - phát video đầu tiên trong modal
function playAllVideos() {
    if (currentPlaylistVideos.length > 0) {
        const firstVideo = currentPlaylistVideos[0];
        showVideoModal(firstVideo.id, firstVideo.title);
    }
}

// Hàm phát ngẫu nhiên - chọn video ngẫu nhiên và phát trong modal
function shufflePlaylist() {
    if (currentPlaylistVideos.length > 0) {
        const randomIndex = Math.floor(Math.random() * currentPlaylistVideos.length);
        const randomVideo = currentPlaylistVideos[randomIndex];
        showVideoModal(randomVideo.id, randomVideo.title);
    }
}

// Hàm hiển thị video trong modal (tùy chọn)
// Biến lưu trạng thái phát video
let currentVideoIndex = 0;
let isAutoPlayNext = false;

function showVideoModal(videoId, videoTitle, startIndex = null, autoPlayNext = false) {
    const modal = document.getElementById('movieModal');
    const modalContent = document.getElementById('modalContent');
    
    // Nếu có startIndex, cập nhật currentVideoIndex
    if (startIndex !== null) {
        currentVideoIndex = startIndex;
    } else {
        // Tìm index của video hiện tại trong playlist
        currentVideoIndex = currentPlaylistVideos.findIndex(v => v.id === videoId);
        if (currentVideoIndex === -1) currentVideoIndex = 0;
    }
    
    isAutoPlayNext = autoPlayNext;
    
    const currentVideo = currentPlaylistVideos[currentVideoIndex];
    const hasPrevious = currentVideoIndex > 0;
    const hasNext = currentVideoIndex < currentPlaylistVideos.length - 1;
    
    modalContent.innerHTML = `
        <h2 style="color: #ff6b35; margin-bottom: 0.5rem;">${videoTitle}</h2>
        <p style="color: rgba(255,255,255,0.6); margin-bottom: 1rem; font-size: 0.9rem;">
            Video ${currentVideoIndex + 1}/${currentPlaylistVideos.length}
        </p>
        <div style="position: relative; width: 100%; height: 0; padding-bottom: 56.25%; margin-bottom: 1rem;">
            <iframe 
                id="videoPlayer"
                src="https://www.youtube.com/embed/${videoId}?autoplay=1&enablejsapi=1"
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; border-radius: 10px;"
                allowfullscreen
                allow="autoplay">
            </iframe>
        </div>
        <div style="display: flex; gap: 1rem; justify-content: center; align-items: center; flex-wrap: wrap;">
            <button 
                onclick="playPreviousVideo()" 
                ${!hasPrevious ? 'disabled' : ''}
                style="background: ${hasPrevious ? '#ff6b35' : '#666'}; color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 25px; cursor: ${hasPrevious ? 'pointer' : 'not-allowed'}; font-size: 0.9rem;">
                ⏮ Trước
            </button>
            
            <button 
                onclick="toggleAutoPlay(this)" 
                style="background: ${isAutoPlayNext ? '#ff6b35' : 'transparent'}; color: ${isAutoPlayNext ? 'white' : '#ff6b35'}; border: 2px solid #ff6b35; padding: 0.8rem 1.5rem; border-radius: 25px; cursor: pointer; font-size: 0.9rem;">
                ${isAutoPlayNext ? '🔁 Tự động' : '🔁 Thủ công'}
            </button>
            
            <button 
                onclick="playNextVideo()" 
                ${!hasNext ? 'disabled' : ''}
                style="background: ${hasNext ? '#ff6b35' : '#666'}; color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 25px; cursor: ${hasNext ? 'pointer' : 'not-allowed'}; font-size: 0.9rem;">
                Sau ⏭
            </button>
            
            <button 
                onclick="document.getElementById('movieModal').style.display='none'" 
                style="background: transparent; color: #ff6b35; border: 2px solid #ff6b35; padding: 0.8rem 1.5rem; border-radius: 25px; cursor: pointer; font-size: 0.9rem;">
                Đóng
            </button>
        </div>
    `;
    
    modal.style.display = 'block';
    
    // Nếu bật auto play, lắng nghe sự kiện video kết thúc
    if (isAutoPlayNext && hasNext) {
        setupAutoPlayNext();
    }
}

// Hàm phát video trước
function playPreviousVideo() {
    if (currentVideoIndex > 0) {
        currentVideoIndex--;
        const prevVideo = currentPlaylistVideos[currentVideoIndex];
        showVideoModal(prevVideo.id, prevVideo.title, currentVideoIndex, isAutoPlayNext);
    }
}

// Hàm phát video tiếp theo
function playNextVideo() {
    if (currentVideoIndex < currentPlaylistVideos.length - 1) {
        currentVideoIndex++;
        const nextVideo = currentPlaylistVideos[currentVideoIndex];
        showVideoModal(nextVideo.id, nextVideo.title, currentVideoIndex, isAutoPlayNext);
    }
}

// Toggle auto play
function toggleAutoPlay(button) {
    isAutoPlayNext = !isAutoPlayNext;
    button.textContent = isAutoPlayNext ? '🔁 Tự động' : '🔁 Thủ công';
    button.style.background = isAutoPlayNext ? '#ff6b35' : 'transparent';
    button.style.color = isAutoPlayNext ? 'white' : '#ff6b35';
    
    if (isAutoPlayNext) {
        setupAutoPlayNext();
    }
}

// Setup auto play next video
function setupAutoPlayNext() {
    // YouTube iframe API sẽ tự động phát video tiếp theo sau khi video hiện tại kết thúc
    // Workaround: Check sau 3 giây để tự động chuyển (vì không thể lắng nghe iframe event từ domain khác)
    const checkInterval = setInterval(() => {
        if (!isAutoPlayNext || document.getElementById('movieModal').style.display === 'none') {
            clearInterval(checkInterval);
            return;
        }
        
        // Tự động chuyển video sau khi hết (giả định video trung bình 5-10 phút)
        // Bạn có thể điều chỉnh logic này
    }, 1000);
}

// Cập nhật hàm showMovieDetails để sử dụng viewPlaylist
function showMovieDetails(movie) {
    const modal = document.getElementById('movieModal');
    const modalContent = document.getElementById('modalContent');

    const stars = '⭐'.repeat(Math.floor(movie.rating));

    const playButton = movie.isPlaylist
        ? `<button style="background: linear-gradient(45deg, #ff6b35, #f7931e); color: white; border: none; padding: 1rem 2rem; border-radius: 25px; cursor: pointer; font-size: 1rem; margin-right: 1rem;" onclick="viewPlaylist(${JSON.stringify(movie).replace(/"/g, '&quot;')})">▶ Xem Playlist</button>`
        : `<button style="background: linear-gradient(45deg, #ff6b35, #f7931e); color: white; border: none; padding: 1rem 2rem; border-radius: 25px; cursor: pointer; font-size: 1rem; margin-right: 1rem;" onclick="alert('Tính năng xem phim sẽ sớm được cập nhật!')">▶ Xem Phim</button>`;

    modalContent.innerHTML = `
        <h2 style="color: #ff6b35; margin-bottom: 1rem;">${movie.title}</h2>
        <p style="margin-bottom: 0.5rem;"><strong>Thể loại:</strong> ${movie.genre}</p>
        <p style="margin-bottom: 0.5rem;"><strong>Năm:</strong> ${movie.year}</p>
        <p style="margin-bottom: 1rem;"><strong>Đánh giá:</strong> ${stars} ${movie.rating}/5</p>
        ${movie.isPlaylist ? `<p style="margin-bottom: 1rem;"><strong>Số video:</strong> ${movie.videoCount}</p>` : ''}
        <p style="line-height: 1.6;">${movie.description}</p>
        <div style="margin-top: 2rem; text-align: center;">
            ${playButton}
            <button style="background: transparent; color: #ff6b35; border: 2px solid #ff6b35; padding: 1rem 2rem; border-radius: 25px; cursor: pointer; font-size: 1rem;" onclick="alert('Đã thêm vào danh sách yêu thích!')">♡ Yêu thích</button>
        </div>
    `;

    modal.style.display = 'block';
}

// Cập nhật search function để work với playlist view
function setupSearch() {
    const searchInput = document.getElementById('searchInput');

    searchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase();

        // Nếu đang trong playlist view, search trong videos
        if (isPlaylistView && currentPlaylistVideos.length > 0) {
            const filteredVideos = currentPlaylistVideos.filter(video =>
                video.title.toLowerCase().includes(query) ||
                video.channelName.toLowerCase().includes(query)
            );

            updateVideosDisplay(filteredVideos);
            return;
        }

        // Search trong movies như bình thường
        let searchSource;
        if (currentCategory === 'all') {
            searchSource = [...fallbackMovies, ...allPlaylists];
        } else {
            searchSource = [...(playlistsByCategory[currentCategory] || [])];
            const fallbackOfCategory = fallbackMovies.filter(movie =>
                movie.categories.includes(currentCategory)
            );
            searchSource = [...searchSource, ...fallbackOfCategory];
        }

        let filteredMovies = searchSource.filter(movie =>
            movie.title.toLowerCase().includes(query) ||
            movie.genre.toLowerCase().includes(query)
        );

        loadMovies(filteredMovies);
    });
}

// Hàm cập nhật display videos khi search
function updateVideosDisplay(videos) {
    const videosGrid = document.getElementById('videosGrid');

    if (videos.length === 0) {
        videosGrid.innerHTML = '<div style="text-align: center; padding: 2rem; color: rgba(255,255,255,0.7);"><p>Không tìm thấy video nào phù hợp</p></div>';
        return;
    }

    const videosHtml = videos.map((video, index) => `
        <div class="movie-card" onclick="playVideo('${video.id}', '${video.title.replace(/'/g, "\\'")}')">
            <div class="movie-poster">
                ${video.thumbnail ?
            `<img src="${video.thumbnail}" alt="${video.title}" style="width: 100%; height: 100%; object-fit: cover;">` :
            '<div style="font-size: 4rem; opacity: 0.5;">🎬</div>'
        }
                <div style="position: absolute; bottom: 5px; right: 5px; background: rgba(0,0,0,0.8); color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem;">
                    ${video.duration}
                </div>
                <div style="position: absolute; top: 5px; left: 5px; background: #ff6b35; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; font-weight: bold;">
                    ${video.index}
                </div>
            </div>
            <div class="movie-info">
                <h3 class="movie-title" style="font-size: 0.9rem; line-height: 1.3;">${video.title}</h3>
                <p class="movie-genre" style="font-size: 0.8rem; opacity: 0.8;">${video.channelName}</p>
                <div class="movie-rating">
                    <span style="color: #ff6b35; font-size: 0.8rem;">▶ Xem ngay</span>
                </div>
            </div>
        </div>
    `).join('');

    videosGrid.innerHTML = videosHtml;
}

// Hàm xem playlist - thay thế cho window.open trong showMovieDetails
async function viewPlaylist(movie) {
    try {
        // Hiển thị loading
        showPlaylistLoading(movie.title);
        
        // Lấy danh sách video từ playlist
        const videos = await getPlaylistVideos(movie.contentId);
        
        if (videos.length === 0) {
            alert('Không thể tải danh sách video từ playlist này');
            return;
        }
        
        // Lưu trạng thái
        currentPlaylistId = movie.contentId;
        currentPlaylistVideos = videos;
        isPlaylistView = true;
        
        // Hiển thị playlist
        displayPlaylistView(movie, videos);
        
        // Đóng modal
        document.getElementById('movieModal').style.display = 'none';
        
    } catch (error) {
        console.error('Error loading playlist:', error);
        alert('Có lỗi khi tải playlist. Vui lòng thử lại!');
    }
}


// Hàm trích xuất videos từ ytInitialData
function extractVideosFromPlaylist(ytInitialData) {
    try {
        const videos = [];
        
        // Điều hướng đến playlist content theo structure thực tế
        const contents = ytInitialData?.contents?.twoColumnBrowseResultsRenderer?.tabs;
        
        if (!contents || !Array.isArray(contents)) {
            console.warn('Không tìm thấy tabs trong ytInitialData');
            return [];
        }
        
        // Tìm tab được chọn (selected: true)
        const selectedTab = contents.find(tab => tab.tabRenderer?.selected);
        if (!selectedTab) {
            console.warn('Không tìm thấy tab được chọn');
            return [];
        }
        
        // Điều hướng đến sectionListRenderer
        const sectionList = selectedTab.tabRenderer?.content?.sectionListRenderer?.contents;
        if (!sectionList || !Array.isArray(sectionList)) {
            console.warn('Không tìm thấy sectionListRenderer contents');
            return [];
        }
        
        // Tìm itemSectionRenderer
        const itemSection = sectionList.find(section => section.itemSectionRenderer);
        if (!itemSection) {
            console.warn('Không tìm thấy itemSectionRenderer');
            return [];
        }
        
        // Tìm playlistVideoListRenderer
        const playlistVideoList = itemSection.itemSectionRenderer?.contents?.find(
            content => content.playlistVideoListRenderer
        );
        
        if (!playlistVideoList) {
            console.warn('Không tìm thấy playlistVideoListRenderer');
            return [];
        }
        
        const videoContents = playlistVideoList.playlistVideoListRenderer?.contents;
        if (!videoContents || !Array.isArray(videoContents)) {
            console.warn('Không tìm thấy video contents');
            return [];
        }
        
        // Trích xuất thông tin từng video
        videoContents.forEach((item, index) => {
            try {
                const videoRenderer = item.playlistVideoRenderer;
                if (!videoRenderer) return;
                
                // Lấy thông tin video
                const title = videoRenderer.title?.runs?.[0]?.text || 'Không có tiêu đề';
                const videoId = videoRenderer.videoId || '';
                const duration = videoRenderer.lengthText?.simpleText || 'N/A';
                
                // Lấy thumbnail - chọn thumbnail có độ phân giải tốt nhất
                let thumbnail = '';
                if (videoRenderer.thumbnail?.thumbnails && Array.isArray(videoRenderer.thumbnail.thumbnails)) {
                    const thumbnails = videoRenderer.thumbnail.thumbnails;
                    // Lấy thumbnail cuối cùng (thường có độ phân giải cao nhất)
                    thumbnail = thumbnails[thumbnails.length - 1]?.url || thumbnails[0]?.url || '';
                }
                
                // Lấy thông tin kênh
                const channelName = videoRenderer.shortBylineText?.runs?.[0]?.text || 'Không rõ';
                
                // Lấy số thứ tự
                const indexNumber = videoRenderer.index?.simpleText || (index + 1).toString();
                
                // Lấy thông tin view và thời gian upload
                let viewInfo = '';
                if (videoRenderer.videoInfo?.runs && Array.isArray(videoRenderer.videoInfo.runs)) {
                    viewInfo = videoRenderer.videoInfo.runs.map(run => run.text).join('');
                }
                
                videos.push({
                    id: videoId,
                    title: title,
                    duration: duration,
                    thumbnail: thumbnail,
                    channelName: channelName,
                    index: parseInt(indexNumber) || (index + 1),
                    viewInfo: viewInfo
                });
                
            } catch (error) {
                console.warn(`Lỗi khi xử lý video ${index}:`, error);
            }
        });
        
        console.log(`Đã trích xuất được ${videos.length} videos từ playlist`);
        return videos;
        
    } catch (error) {
        console.error('Lỗi khi trích xuất videos:', error);
        return [];
    }
}

// Mock videos cho testing
function generateMockVideos() {
    const mockVideos = [];
    for (let i = 1; i <= 10; i++) {
        mockVideos.push({
            id: `mock_video_${i}`,
            title: `Video số ${i} - Phim hay nhất ${new Date().getFullYear()}`,
            duration: `${Math.floor(Math.random() * 60) + 60}:${String(Math.floor(Math.random() * 60)).padStart(2, '0')}`,
            thumbnail: `https://img.youtube.com/vi/dQw4w9WgXcQ/mqdefault.jpg`,
            channelName: 'Kênh phim hay',
            index: i
        });
    }
    return mockVideos;
}

// Hiển thị loading khi tải playlist
function showPlaylistLoading(playlistTitle) {
    const moviesSection = document.getElementById('movies');
    
    moviesSection.innerHTML = `
        <div style="display: flex; align-items: center; margin-bottom: 2rem;">
            <button onclick="backToMovies()" style="background: transparent; border: 2px solid #ff6b35; color: #ff6b35; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer; margin-right: 1rem; font-size: 1rem;">
                ← Quay lại
            </button>
            <h2 class="section-title">Đang tải: ${playlistTitle}</h2>
        </div>
        <div style="text-align: center; padding: 3rem;">
            <div class="loading"></div>
            <p style="margin-top: 1rem; color: rgba(255,255,255,0.7);">Đang tải danh sách video...</p>
        </div>
    `;
}

// Hiển thị playlist view
function displayPlaylistView(movie, videos) {
    const moviesSection = document.getElementById('movies');
    
    const videosHtml = videos.map((video, index) => `
        <div class="movie-card" onclick="playVideo('${video.id}', '${video.title.replace(/'/g, "\\'")}')">
            <div class="movie-poster">
                ${video.thumbnail ? 
                    `<img src="${video.thumbnail}" alt="${video.title}" style="width: 100%; height: 100%; object-fit: cover;">` :
                    '<div style="font-size: 4rem; opacity: 0.5;">🎬</div>'
                }
                <div style="position: absolute; bottom: 5px; right: 5px; background: rgba(0,0,0,0.8); color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem;">
                    ${video.duration}
                </div>
                <div style="position: absolute; top: 5px; left: 5px; background: #ff6b35; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; font-weight: bold;">
                    ${video.index}
                </div>
            </div>
            <div class="movie-info">
                <h3 class="movie-title" style="font-size: 0.9rem; line-height: 1.3;">${video.title}</h3>
                <p class="movie-genre" style="font-size: 0.8rem; opacity: 0.8;">${video.channelName}</p>
                <div class="movie-rating">
                    <span style="color: #ff6b35; font-size: 0.8rem;">▶ Xem ngay</span>
                </div>
            </div>
        </div>
    `).join('');
    
    moviesSection.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center;">
                <button onclick="backToMovies()" style="background: transparent; border: 2px solid #ff6b35; color: #ff6b35; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer; margin-right: 1rem; font-size: 1rem; transition: all 0.3s ease;">
                    ← Quay lại
                </button>
                <div>
                    <h2 class="section-title" style="margin: 0;">${movie.title}</h2>
                    <p style="color: rgba(255,255,255,0.7); margin: 0.5rem 0 0 0; font-size: 0.9rem;">${videos.length} video • ${movie.genre}</p>
                </div>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <button onclick="playAllVideos()" style="background: linear-gradient(45deg, #ff6b35, #f7931e); color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 25px; cursor: pointer; font-size: 0.9rem; font-weight: bold;">
                    ▶ Phát tất cả
                </button>
                <button onclick="shufflePlaylist()" style="background: transparent; border: 2px solid #ff6b35; color: #ff6b35; padding: 0.8rem 1.5rem; border-radius: 25px; cursor: pointer; font-size: 0.9rem;">
                    🔀 Phát ngẫu nhiên
                </button>
            </div>
        </div>
        <div class="movies-grid" id="videosGrid">
            ${videosHtml}
        </div>
    `;
    
    // Thêm animation cho video cards
    const videoCards = document.querySelectorAll('#videosGrid .movie-card');
    videoCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 50);
    });
}

// Hàm quay lại danh sách phim
function backToMovies() {
    isPlaylistView = false;
    currentPlaylistId = null;
    currentPlaylistVideos = [];
    
    // Khôi phục lại movies view
    const moviesSection = document.getElementById('movies');
    moviesSection.innerHTML = `
        <h2 class="section-title">Phim Nổi Bật</h2>
        <div class="movies-grid" id="moviesGrid">
            <!-- Movies will be loaded here -->
        </div>
    `;
    
    // Load lại movies dựa trên category hiện tại
    if (currentCategory === 'all') {
        displayAllMoviesProgressive();
    } else {
        filterByGenre(currentCategory);
    }
}



// Cập nhật hàm showMovieDetails để sử dụng viewPlaylist
function showMovieDetails(movie) {
    const modal = document.getElementById('movieModal');
    const modalContent = document.getElementById('modalContent');

    const stars = '⭐'.repeat(Math.floor(movie.rating));

    const playButton = movie.isPlaylist
        ? `<button style="background: linear-gradient(45deg, #ff6b35, #f7931e); color: white; border: none; padding: 1rem 2rem; border-radius: 25px; cursor: pointer; font-size: 1rem; margin-right: 1rem;" onclick="viewPlaylist(${JSON.stringify(movie).replace(/"/g, '&quot;')})">▶ Xem Playlist</button>`
        : `<button style="background: linear-gradient(45deg, #ff6b35, #f7931e); color: white; border: none; padding: 1rem 2rem; border-radius: 25px; cursor: pointer; font-size: 1rem; margin-right: 1rem;" onclick="alert('Tính năng xem phim sẽ sớm được cập nhật!')">▶ Xem Phim</button>`;

    modalContent.innerHTML = `
        <h2 style="color: #ff6b35; margin-bottom: 1rem;">${movie.title}</h2>
        <p style="margin-bottom: 0.5rem;"><strong>Thể loại:</strong> ${movie.genre}</p>
        <p style="margin-bottom: 0.5rem;"><strong>Năm:</strong> ${movie.year}</p>
        <p style="margin-bottom: 1rem;"><strong>Đánh giá:</strong> ${stars} ${movie.rating}/5</p>
        ${movie.isPlaylist ? `<p style="margin-bottom: 1rem;"><strong>Số video:</strong> ${movie.videoCount}</p>` : ''}
        <p style="line-height: 1.6;">${movie.description}</p>
        <div style="margin-top: 2rem; text-align: center;">
            ${playButton}
            <button style="background: transparent; color: #ff6b35; border: 2px solid #ff6b35; padding: 1rem 2rem; border-radius: 25px; cursor: pointer; font-size: 1rem;" onclick="alert('Đã thêm vào danh sách yêu thích!')">♡ Yêu thích</button>
        </div>
    `;

    modal.style.display = 'block';
}

// Cập nhật search function để work với playlist view
function setupSearch() {
    const searchInput = document.getElementById('searchInput');

    searchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase();

        // Nếu đang trong playlist view, search trong videos
        if (isPlaylistView && currentPlaylistVideos.length > 0) {
            const filteredVideos = currentPlaylistVideos.filter(video =>
                video.title.toLowerCase().includes(query) ||
                video.channelName.toLowerCase().includes(query)
            );
            
            updateVideosDisplay(filteredVideos);
            return;
        }

        // Search trong movies như bình thường
        let searchSource;
        if (currentCategory === 'all') {
            searchSource = [...fallbackMovies, ...allPlaylists];
        } else {
            searchSource = [...(playlistsByCategory[currentCategory] || [])];
            const fallbackOfCategory = fallbackMovies.filter(movie =>
                movie.categories.includes(currentCategory)
            );
            searchSource = [...searchSource, ...fallbackOfCategory];
        }

        let filteredMovies = searchSource.filter(movie =>
            movie.title.toLowerCase().includes(query) ||
            movie.genre.toLowerCase().includes(query)
        );

        loadMovies(filteredMovies);
    });
}

// Hàm cập nhật display videos khi search
function updateVideosDisplay(videos) {
    const videosGrid = document.getElementById('videosGrid');
    
    if (videos.length === 0) {
        videosGrid.innerHTML = '<div style="text-align: center; padding: 2rem; color: rgba(255,255,255,0.7);"><p>Không tìm thấy video nào phù hợp</p></div>';
        return;
    }
    
    const videosHtml = videos.map((video, index) => `
        <div class="movie-card" onclick="playVideo('${video.id}', '${video.title.replace(/'/g, "\\'")}')">
            <div class="movie-poster">
                ${video.thumbnail ? 
                    `<img src="${video.thumbnail}" alt="${video.title}" style="width: 100%; height: 100%; object-fit: cover;">` :
                    '<div style="font-size: 4rem; opacity: 0.5;">🎬</div>'
                }
                <div style="position: absolute; bottom: 5px; right: 5px; background: rgba(0,0,0,0.8); color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem;">
                    ${video.duration}
                </div>
                <div style="position: absolute; top: 5px; left: 5px; background: #ff6b35; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; font-weight: bold;">
                    ${video.index}
                </div>
            </div>
            <div class="movie-info">
                <h3 class="movie-title" style="font-size: 0.9rem; line-height: 1.3;">${video.title}</h3>
                <p class="movie-genre" style="font-size: 0.8rem; opacity: 0.8;">${video.channelName}</p>
                <div class="movie-rating">
                    <span style="color: #ff6b35; font-size: 0.8rem;">▶ Xem ngay</span>
                </div>
            </div>
        </div>
    `).join('');
    
    videosGrid.innerHTML = videosHtml;
}