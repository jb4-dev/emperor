<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Emperor Browser</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background-color: #0d0d0d;
      color: #e0e0e0;
      font-family: Helvetica, Arial, sans-serif;
      min-height: 100vh;
    }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 20px;
    }

    /* Landing Page */
    .landing {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      text-align: center;
    }

    .logo-container {
      margin-bottom: 40px;
    }

    .logo-container svg {
      width: 150px;
      height: 150px;
      filter: brightness(0) saturate(100%);
    }

    .logo-container svg path {
      fill: #1a1560 !important;
    }

    h1 {
      font-size: 3em;
      margin-bottom: 40px;
      color: #4a9eff;
      font-weight: 300;
      letter-spacing: 2px;
    }

    .search-container {
      width: 100%;
      max-width: 600px;
      position: relative;
      margin-bottom: 30px;
    }

    .search-input {
      width: 100%;
      padding: 15px 50px 15px 20px;
      background-color: #1a1a1a;
      border: 2px solid #2a2a2a;
      border-radius: 8px;
      color: #e0e0e0;
      font-size: 16px;
      font-family: Helvetica, Arial, sans-serif;
      transition: border-color 0.3s;
    }

    .search-input:focus {
      outline: none;
      border-color: #4a9eff;
    }

    .search-btn {
      position: absolute;
      right: 5px;
      top: 50%;
      transform: translateY(-50%);
      background-color: #4a9eff;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      color: white;
      cursor: pointer;
      font-family: Helvetica, Arial, sans-serif;
      transition: background-color 0.3s;
    }

    .search-btn:hover {
      background-color: #3a8eef;
    }

    .autocomplete-dropdown {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background-color: #1a1a1a;
      border: 2px solid #4a9eff;
      border-top: none;
      border-radius: 0 0 8px 8px;
      max-height: 300px;
      overflow-y: auto;
      z-index: 1000;
      display: none;
    }

    .autocomplete-dropdown.active {
      display: block;
    }

    .autocomplete-item {
      padding: 12px 20px;
      cursor: pointer;
      border-bottom: 1px solid #2a2a2a;
      transition: background-color 0.2s;
    }

    .autocomplete-item:hover,
    .autocomplete-item.selected {
      background-color: #2a2a2a;
    }

    .autocomplete-item:last-child {
      border-bottom: none;
    }

    .tag-category {
      display: inline-block;
      padding: 2px 6px;
      border-radius: 3px;
      font-size: 11px;
      margin-right: 8px;
      font-weight: bold;
    }

    .tag-general { background-color: #0073ff; }
    .tag-artist { background-color: #ff4444; }
    .tag-copyright { background-color: #dd00dd; }
    .tag-character { background-color: #00aa00; }
    .tag-meta { background-color: #ff8800; }

    .copyright-info {
      margin-top: 40px;
      font-size: 14px;
      color: #666;
      line-height: 1.6;
    }

    .version {
      margin-top: 10px;
      font-size: 12px;
      color: #555;
    }

    /* Gallery View */
    .gallery {
      display: none;
    }

    .gallery.active {
      display: block;
    }

    .header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 0;
      border-bottom: 2px solid #2a2a2a;
      margin-bottom: 30px;
      flex-wrap: wrap;
      gap: 15px;
    }

    .header-left {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .small-logo svg {
      width: 40px;
      height: 40px;
    }

    .header-title {
      font-size: 1.5em;
      color: #4a9eff;
      font-weight: 300;
      cursor: pointer;
    }

    .header-search {
      flex: 1;
      min-width: 300px;
      max-width: 500px;
    }

    .image-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .image-card {
      background-color: #1a1a1a;
      border-radius: 8px;
      overflow: hidden;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .image-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(74, 158, 255, 0.3);
    }

    .image-wrapper {
      width: 100%;
      padding-top: 100%;
      position: relative;
      background-color: #0d0d0d;
    }

    .image-wrapper img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .image-info {
      padding: 10px;
      font-size: 12px;
      color: #999;
    }

    .loading {
      text-align: center;
      padding: 40px;
      font-size: 18px;
      color: #4a9eff;
    }

    .load-more {
      display: block;
      width: 200px;
      margin: 30px auto;
      padding: 15px;
      background-color: #4a9eff;
      border: none;
      border-radius: 8px;
      color: white;
      font-size: 16px;
      font-family: Helvetica, Arial, sans-serif;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .load-more:hover {
      background-color: #3a8eef;
    }

    .load-more:disabled {
      background-color: #2a2a2a;
      cursor: not-allowed;
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.95);
      z-index: 2000;
      padding: 20px;
      overflow-y: auto;
    }

    .modal.active {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      max-width: 90vw;
      max-height: 90vh;
      position: relative;
    }

    .modal-content img {
      max-width: 100%;
      max-height: 90vh;
      object-fit: contain;
    }

    .modal-close {
      position: fixed;
      top: 20px;
      right: 20px;
      background-color: #4a9eff;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      color: white;
      font-size: 18px;
      cursor: pointer;
      z-index: 2001;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      h1 {
        font-size: 2em;
      }

      .logo-container svg {
        width: 100px;
        height: 100px;
      }

      .image-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
      }

      .header {
        flex-direction: column;
      }

      .header-search {
        width: 100%;
        max-width: 100%;
      }

      .search-input {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>
  <!-- Landing Page -->
  <div class="landing" id="landing">
    <div class="logo-container">
      <div id="logoContainer"></div>
    </div>
    <h1>Emperor Browser</h1>
    <div class="search-container">
      <input 
        type="text" 
        class="search-input" 
        id="mainSearch" 
        placeholder="Search tags..."
        autocomplete="off"
      />
      <button class="search-btn" onclick="performSearch()">Search</button>
      <div class="autocomplete-dropdown" id="mainAutocomplete"></div>
    </div>
    <div class="copyright-info">
      © 2025 Emperor Browser. All rights reserved.<br>
      Powered by The PGN API
      <div class="version">Version 1.0.0</div>
    </div>
  </div>

  <!-- Gallery View -->
  <div class="gallery" id="gallery">
    <div class="container">
      <div class="header">
        <div class="header-left">
          <div class="small-logo" id="smallLogo" onclick="goHome()"></div>
          <div class="header-title" onclick="goHome()">Emperor Browser</div>
        </div>
        <div class="header-search search-container">
          <input 
            type="text" 
            class="search-input" 
            id="gallerySearch" 
            placeholder="Search tags..."
            autocomplete="off"
          />
          <button class="search-btn" onclick="performSearch()">Search</button>
          <div class="autocomplete-dropdown" id="galleryAutocomplete"></div>
        </div>
      </div>
      <div class="image-grid" id="imageGrid"></div>
      <div class="loading" id="loading" style="display: none;">Loading...</div>
      <button class="load-more" id="loadMore" onclick="loadMore()" style="display: none;">Load More</button>
    </div>
  </div>

  <!-- Image Modal -->
  <div class="modal" id="modal" onclick="closeModal()">
    <button class="modal-close">✕</button>
    <div class="modal-content" onclick="event.stopPropagation()">
      <img id="modalImage" src="" alt="Full size image">
    </div>
  </div>

  <script>
    // Check authorization
    if (sessionStorage.getItem("authorized") !== "true") {
      window.location.href = "/";
    }

    // Configuration
    const API_BASE = 'https://test-server.12nineteen.com';
    const API_USER = 'your_username'; // Replace with your username
    const API_KEY = 'your_api_key'; // Replace with your API key
    const DENYLIST = [
      'loli', 'shota', 'toddlercon', 'child', 'young', 'underage',
      'gore', 'guro', 'scat', 'vore', 'bestiality'
    ];

    let currentPage = 1;
    let currentTags = '';
    let isLoading = false;
    let lastPostId = null;

    // Load and modify logo SVG
    async function loadLogo() {
      try {
        const response = await fetch('proxy.php?action=logo');
        const svgText = await response.text();
        
        // Modify SVG to change color
        const modifiedSvg = svgText.replace(/fill="[^"]*"/g, 'fill="#1a1560"');
        
        document.getElementById('logoContainer').innerHTML = modifiedSvg;
        document.getElementById('smallLogo').innerHTML = modifiedSvg;
      } catch (error) {
        console.error('Error loading logo:', error);
      }
    }

    // Autocomplete functionality
    let autocompleteTimeout;
    let selectedIndex = -1;

    function setupAutocomplete(inputId, dropdownId) {
      const input = document.getElementById(inputId);
      const dropdown = document.getElementById(dropdownId);

      input.addEventListener('input', function() {
        clearTimeout(autocompleteTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
          dropdown.classList.remove('active');
          return;
        }

        // Get the last tag being typed
        const tags = query.split(' ');
        const currentTag = tags[tags.length - 1];

        if (currentTag.length < 2) {
          dropdown.classList.remove('active');
          return;
        }

        autocompleteTimeout = setTimeout(() => {
          fetchTagSuggestions(currentTag, dropdown, input);
        }, 300);
      });

      input.addEventListener('keydown', function(e) {
        const items = dropdown.querySelectorAll('.autocomplete-item');
        
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
          updateSelection(items);
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          selectedIndex = Math.max(selectedIndex - 1, -1);
          updateSelection(items);
        } else if (e.key === 'Enter' && selectedIndex >= 0) {
          e.preventDefault();
          items[selectedIndex].click();
        }
      });

      document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
          dropdown.classList.remove('active');
        }
      });
    }

    function updateSelection(items) {
      items.forEach((item, index) => {
        if (index === selectedIndex) {
          item.classList.add('selected');
          item.scrollIntoView({ block: 'nearest' });
        } else {
          item.classList.remove('selected');
        }
      });
    }

    async function fetchTagSuggestions(query, dropdown, input) {
      try {
        const response = await fetch(`proxy.php?action=autocomplete&query=${encodeURIComponent(query)}`);
        const tags = await response.json();

        if (tags.length === 0) {
          dropdown.classList.remove('active');
          return;
        }

        dropdown.innerHTML = tags.map(tag => {
          const categoryClass = `tag-${tag.category || 'general'}`;
          const categoryLabel = (tag.category || 'general').charAt(0).toUpperCase();
          
          return `
            <div class="autocomplete-item" onclick="selectTag('${tag.name}', '${input.id}', '${dropdown.id}')">
              <span class="tag-category ${categoryClass}">${categoryLabel}</span>
              ${tag.name.replace(/_/g, ' ')}
              ${tag.post_count ? `<span style="color: #666; float: right;">${tag.post_count}</span>` : ''}
            </div>
          `;
        }).join('');

        dropdown.classList.add('active');
        selectedIndex = -1;
      } catch (error) {
        console.error('Error fetching tags:', error);
      }
    }

    function selectTag(tagName, inputId, dropdownId) {
      const input = document.getElementById(inputId);
      const dropdown = document.getElementById(dropdownId);
      
      const tags = input.value.trim().split(' ');
      tags[tags.length - 1] = tagName;
      input.value = tags.join(' ') + ' ';
      
      dropdown.classList.remove('active');
      input.focus();
    }

    // Search and display functions
    function performSearch() {
      const mainSearch = document.getElementById('mainSearch').value.trim();
      const gallerySearch = document.getElementById('gallerySearch').value.trim();
      const searchQuery = mainSearch || gallerySearch;

      currentTags = searchQuery;
      currentPage = 1;
      lastPostId = null;

      document.getElementById('landing').classList.remove('active');
      document.getElementById('gallery').classList.add('active');
      document.getElementById('gallerySearch').value = searchQuery;
      document.getElementById('imageGrid').innerHTML = '';

      loadPosts();
    }

    async function loadPosts() {
      if (isLoading) return;
      isLoading = true;
      document.getElementById('loading').style.display = 'block';
      document.getElementById('loadMore').style.display = 'none';

      try {
        // Build tag query with denylist
        const tagQuery = currentTags + ' ' + DENYLIST.map(tag => `-${tag}`).join(' ');
        
        let url = `proxy.php?action=posts&tags=${encodeURIComponent(tagQuery)}&limit=40`;
        if (lastPostId) {
          url += `&page=b${lastPostId}`;
        }

        const response = await fetch(url);
        const posts = await response.json();

        if (posts.length === 0) {
          document.getElementById('loading').innerHTML = 'No more posts found.';
          return;
        }

        displayPosts(posts);
        lastPostId = posts[posts.length - 1].id;
        document.getElementById('loadMore').style.display = 'block';
      } catch (error) {
        console.error('Error loading posts:', error);
        document.getElementById('loading').innerHTML = 'Error loading posts.';
      } finally {
        isLoading = false;
        document.getElementById('loading').style.display = 'none';
      }
    }

    function displayPosts(posts) {
      const grid = document.getElementById('imageGrid');

      posts.forEach(post => {
        const card = document.createElement('div');
        card.className = 'image-card';
        card.onclick = () => openModal(post.id);

        card.innerHTML = `
          <div class="image-wrapper">
            <img src="proxy.php?action=image&url=${encodeURIComponent(post.preview_file_url || post.file_url)}" 
                 alt="Post ${post.id}" 
                 loading="lazy">
          </div>
          <div class="image-info">
            ID: ${post.id} | Score: ${post.score || 0}
          </div>
        `;

        grid.appendChild(card);
      });
    }

    function loadMore() {
      loadPosts();
    }

    function openModal(postId) {
      fetch(`proxy.php?action=post&id=${postId}`)
        .then(response => response.json())
        .then(post => {
          const modal = document.getElementById('modal');
          const modalImage = document.getElementById('modalImage');
          
          modalImage.src = `proxy.php?action=image&url=${encodeURIComponent(post.large_file_url || post.file_url)}`;
          modal.classList.add('active');
        })
        .catch(error => console.error('Error loading full image:', error));
    }

    function closeModal() {
      document.getElementById('modal').classList.remove('active');
    }

    function goHome() {
      document.getElementById('gallery').classList.remove('active');
      document.getElementById('landing').classList.add('active');
      document.getElementById('mainSearch').value = '';
      document.getElementById('imageGrid').innerHTML = '';
    }

    // Initialize
    loadLogo();
    setupAutocomplete('mainSearch', 'mainAutocomplete');
    setupAutocomplete('gallerySearch', 'galleryAutocomplete');

    // Enter key support
    document.getElementById('mainSearch').addEventListener('keypress', function(e) {
      if (e.key === 'Enter' && selectedIndex < 0) {
        performSearch();
      }
    });

    document.getElementById('gallerySearch').addEventListener('keypress', function(e) {
      if (e.key === 'Enter' && selectedIndex < 0) {
        performSearch();
      }
    });
  </script>
</body>
</html>