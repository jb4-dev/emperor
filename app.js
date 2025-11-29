// Emperor Browser Main Application - Fixed Version

// === AUTHENTICATION & SHIELD ===
const sessionId = localStorage.getItem('session_id');
const shield = document.getElementById('loadingShield');

// Show shield initially
setTimeout(async () => {
  if (!sessionId) {
    window.location.href = '/login.html';
    return;
  }
  
  // Load tags database first
  await loadTagsDatabase();
  
  // Verify session
  fetch(`auth.php?action=check_session&session_id=${sessionId}`)
    .then(res => res.json())
    .then(data => {
      if (!data.valid) {
        localStorage.clear();
        window.location.href = '/login.html';
      } else {
        // Session valid, update user info
        currentUser = data.user;
        document.getElementById('username').textContent = data.user.username;
        document.getElementById('userPoints').textContent = data.user.points;
        
        // Hide shield after 1 second
        setTimeout(() => {
          shield.classList.add('hidden');
        }, 1000);
        
        // Start activity tracking
        startActivityTracking();
      }
    })
    .catch(() => {
      localStorage.clear();
      window.location.href = '/login.html';
    });
}, 100);

// Global user state
let currentUser = null;

// === ACTIVITY TRACKING ===
let activityStartTime = Date.now();

function startActivityTracking() {
  // Track every 5 minutes
  setInterval(() => {
    const minutesActive = Math.floor((Date.now() - activityStartTime) / 60000);
    if (minutesActive >= 5) {
      // Award points for activity
      fetch('points.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=activity&session_id=${sessionId}&minutes=${minutesActive}`
      });
      activityStartTime = Date.now();
    }
  }, 300000); // 5 minutes
}

// === STATE MANAGEMENT ===
const state = {
  currentTags: [],
  currentPage: 1,
  lastPostId: null,
  isLoading: false,
  posts: [],
  currentPost: null
};

// === DOM ELEMENTS ===
const welcomeScreen = document.getElementById('welcomeScreen');
const browseScreen = document.getElementById('browseScreen');
const initialSearch = document.getElementById('initialSearch');
const initialSearchBtn = document.getElementById('initialSearchBtn');
const headerSearch = document.getElementById('headerSearch');
const headerSearchBtn = document.getElementById('headerSearchBtn');
const currentTagsContainer = document.getElementById('currentTags');
const gallery = document.getElementById('gallery');
const loadingIndicator = document.getElementById('loadingIndicator');
const loadMoreContainer = document.getElementById('loadMoreContainer');
const loadMoreBtn = document.getElementById('loadMoreBtn');
const modal = document.getElementById('modal');
const modalClose = document.getElementById('modalClose');
const modalContent = document.getElementById('modalContent');
const errorContainer = document.getElementById('errorContainer');
const logoutBtn = document.getElementById('logoutBtn');
const messagesBtn = document.getElementById('messagesBtn');

// === MESSAGES BUTTON ===
messagesBtn.addEventListener('click', () => {
  window.location.href = 'messages.html';
});

// Bottom nav buttons (mobile)
const bottomBrowse = document.getElementById('bottomBrowse');
const bottomMessages = document.getElementById('bottomMessages');
const bottomSettings = document.getElementById('bottomSettings');

if (bottomBrowse) {
  bottomBrowse.addEventListener('click', () => {
    // Reload page to go back to browse
    window.location.reload();
  });
}

if (bottomMessages) {
  bottomMessages.addEventListener('click', () => {
    // Clear badges before navigating
    const badge = document.getElementById('unreadBadge');
    const bottomBadge = document.getElementById('bottomUnreadBadge');
    if (badge) badge.style.display = 'none';
    if (bottomBadge) bottomBadge.style.display = 'none';
    window.location.href = 'messages.html';
  });
}

if (bottomSettings) {
  bottomSettings.addEventListener('click', () => {
    window.location.href = 'settings.html';
  });
}

// Check for unread messages periodically
async function checkUnreadMessages() {
  try {
    const response = await fetch(`messages.php?action=get_conversations&session_id=${sessionId}`);
    const data = await response.json();
    
    if (data.conversations) {
      const unreadCount = data.conversations.reduce((sum, conv) => sum + (conv.unread_count || 0), 0);
      const badge = document.getElementById('unreadBadge');
      const bottomBadge = document.getElementById('bottomUnreadBadge');
      
      if (unreadCount > 0) {
        // Top badge
        if (badge) {
          badge.textContent = unreadCount;
          badge.style.display = 'inline';
          badge.style.cssText = 'display: inline; background: #ff6b6b; color: #fff; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;';
        }
        // Bottom badge
        if (bottomBadge) {
          bottomBadge.textContent = unreadCount;
          bottomBadge.style.display = 'inline';
        }
      } else {
        if (badge) badge.style.display = 'none';
        if (bottomBadge) bottomBadge.style.display = 'none';
      }
    }
  } catch (err) {
    console.error('Error checking messages:', err);
  }
}

// Clear unread count when navigating to messages
messagesBtn.addEventListener('click', () => {
  // Clear the badges immediately for better UX
  const badge = document.getElementById('unreadBadge');
  const bottomBadge = document.getElementById('bottomUnreadBadge');
  if (badge) badge.style.display = 'none';
  if (bottomBadge) bottomBadge.style.display = 'none';
  window.location.href = 'messages.html';
});

// Check messages every 30 seconds
checkUnreadMessages();
setInterval(checkUnreadMessages, 30000);

// === LOAD LOGOS ===
function loadLogos() {
  fetch('1294802.svg')
    .then(response => response.text())
    .then(svg => {
      svg = svg.replace(/fill="[^"]*"/g, 'fill="#1a1560"')
               .replace(/stroke="[^"]*"/g, 'stroke="#1a1560"');
      
      const navLogo = document.getElementById('navLogo');
      const logoWelcome = document.getElementById('logoWelcome');
      
      if (navLogo) navLogo.innerHTML = svg;
      if (logoWelcome) logoWelcome.innerHTML = svg;
    })
    .catch(err => console.log('Logo not loaded:', err));
}

loadLogos();

// === LOGOUT ===
logoutBtn.addEventListener('click', async () => {
  try {
    const formData = new FormData();
    formData.append('action', 'logout');
    formData.append('session_id', sessionId);
    
    await fetch('auth.php', {
      method: 'POST',
      body: formData
    });
  } catch (err) {
    console.error('Logout error:', err);
  } finally {
    localStorage.clear();
    window.location.href = '/login.html';
  }
});

// === API FUNCTIONS ===
async function apiRequest(endpoint, params = {}) {
  const url = new URL(CONFIG.API_PROXY, window.location.origin);
  url.searchParams.append('endpoint', endpoint);
  
  Object.keys(params).forEach(key => {
    if (params[key] !== null && params[key] !== undefined) {
      url.searchParams.append(key, params[key]);
    }
  });
  
  const response = await fetch(url.toString(), {
    method: 'GET',
    headers: {'Accept': 'application/json'}
  });
  
  if (!response.ok) {
    throw new Error(`API Error: ${response.status}`);
  }
  
  return await response.json();
}

async function searchPosts(apiTags, page = 1) {
  const filteredUserTags = apiTags.filter(tag => !tag.startsWith('rating:'));
  
  const finalTags = [
    ...filteredUserTags,
    CONFIG.DEFAULT_RATING_TAG
  ].filter(t => t).join(' ').trim();
  
  const params = {
    'tags': finalTags,
    'limit': CONFIG.POSTS_PER_PAGE
  };
  
  if (page > 1 && state.lastPostId) {
    params.page = `b${state.lastPostId}`;
  }
  
  return await apiRequest('/posts.json', params);
}

async function autocomplete(query) {
  if (!query || query.length < 2) return [];
  
  try {
    const results = await apiRequest('/autocomplete.json', {
      'search[query]': query,
      'search[type]': 'tag_query',
      'limit': CONFIG.AUTOCOMPLETE_LIMIT
    });
    
    return results || [];
  } catch (err) {
    console.error('Autocomplete error:', err);
    return [];
  }
}

// === AUTOCOMPLETE HANDLING ===
let autocompleteTimeout;

function setupAutocomplete(inputElement, dropdownElement) {
  inputElement.addEventListener('input', function() {
    clearTimeout(autocompleteTimeout);
    const query = this.value.trim();
    
    if (query.length < 2) {
      dropdownElement.classList.remove('show');
      return;
    }
    
    autocompleteTimeout = setTimeout(async () => {
      const results = await autocomplete(query);
      displayAutocomplete(results, dropdownElement, inputElement);
    }, CONFIG.AUTOCOMPLETE_DEBOUNCE_MS);
  });
  
  inputElement.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      dropdownElement.classList.remove('show');
      
      const tagValue = this.value.trim().replace(/\s+/g, '_');
      
      if (this === initialSearch) {
        state.currentTags = [];
        addTag(tagValue);
        performInitialSearch();
      } else {
        addTag(tagValue);
        this.value = '';
      }
    }
  });
  
  document.addEventListener('click', function(e) {
    if (!inputElement.parentElement.contains(e.target) && !dropdownElement.contains(e.target)) {
      dropdownElement.classList.remove('show');
    }
  });
}

function displayAutocomplete(results, dropdownElement, inputElement) {
  if (!results || results.length === 0) {
    dropdownElement.classList.remove('show');
    return;
  }
  
  dropdownElement.innerHTML = '';
  
  results.forEach(item => {
    const div = document.createElement('div');
    div.className = 'autocomplete-item';
    
    const name = item.label || item.value || '';
    const count = item.post_count || 0;
    
    if (name.startsWith('rating:')) return;

    const tagName = name.replace(/\s+/g, '_');
    
    div.innerHTML = `
      <span style="color: ${getTagColor(tagName)}">${escapeHtml(name)}</span>
      <span style="color: #888; font-size: 12px; margin-left: 8px;">(${count})</span>
    `;
    
    div.addEventListener('click', () => {
      inputElement.value = tagName;
      dropdownElement.classList.remove('show');
      inputElement.focus();
    });
    
    dropdownElement.appendChild(div);
  });
  
  dropdownElement.classList.add('show');
}

setupAutocomplete(initialSearch, document.getElementById('autocompleteInitial'));
setupAutocomplete(headerSearch, document.getElementById('autocompleteHeader'));

// === SEARCH FUNCTIONS ===
function performInitialSearch() {
  const query = initialSearch.value.trim();
  if (!query) return;
  
  state.currentTags = query.replace(/\s+/g, ' ').split(' ')
    .filter(t => t && !t.startsWith('rating:'));
  
  state.currentPage = 1;
  state.posts = [];
  state.lastPostId = null;
  
  welcomeScreen.classList.add('hidden');
  browseScreen.classList.add('active');
  
  updateCurrentTags();
  loadPosts();
}

function addTag(tag) {
  if (!tag) return;
  
  const tags = tag.replace(/\s+/g, ' ').split(' ')
    .filter(t => t && !t.startsWith('rating:'));
  
  tags.forEach(t => {
    if (!state.currentTags.includes(t)) {
      state.currentTags.push(t);
    }
  });
  
  state.currentPage = 1;
  state.posts = [];
  state.lastPostId = null;
  
  updateCurrentTags();
  loadPosts();
}

function removeTag(tag) {
  state.currentTags = state.currentTags.filter(t => t !== tag);
  state.currentPage = 1;
  state.posts = [];
  state.lastPostId = null;
  
  updateCurrentTags();
  
  // If no tags left, show welcome screen
  if (state.currentTags.length === 0) {
    browseScreen.classList.remove('active');
    welcomeScreen.classList.remove('hidden');
    gallery.innerHTML = '';
    return;
  }
  
  loadPosts();
}

function clearAllTags() {
  state.currentTags = [];
  state.currentPage = 1;
  state.posts = [];
  state.lastPostId = null;
  
  browseScreen.classList.remove('active');
  welcomeScreen.classList.remove('hidden');
  gallery.innerHTML = '';
  initialSearch.value = '';
  headerSearch.value = '';
  updateCurrentTags();
}

function updateCurrentTags() {
  if (state.currentTags.length === 0) {
    currentTagsContainer.innerHTML = '<div style="color: #666; font-style: italic;">No tags selected</div>';
    return;
  }

  const apiTags = state.currentTags.slice(0, 2);
  const localTags = state.currentTags.slice(2);

  currentTagsContainer.innerHTML = '';

  // Add clear all button
  const clearAllBtn = document.createElement('button');
  clearAllBtn.textContent = '✕ Clear All';
  clearAllBtn.style.cssText = `
    padding: 4px 10px;
    background: #ff6b6b;
    border: 1px solid #ff8888;
    border-radius: 12px;
    color: #fff;
    font-size: 12px;
    cursor: pointer;
    margin: 2px;
    transition: all 0.2s ease;
  `;
  clearAllBtn.addEventListener('mouseenter', () => {
    clearAllBtn.style.background = '#ff4444';
  });
  clearAllBtn.addEventListener('mouseleave', () => {
    clearAllBtn.style.background = '#ff6b6b';
  });
  clearAllBtn.addEventListener('click', clearAllTags);
  currentTagsContainer.appendChild(clearAllBtn);

  if (apiTags.length > 0) {
    const apiLabel = document.createElement('div');
    apiLabel.style.cssText = 'color: #4a9eff; font-size: 12px; margin-bottom: 5px; width: 100%;';
    apiLabel.textContent = 'API Search Tags (Max 2)';
    currentTagsContainer.appendChild(apiLabel);
    
    apiTags.forEach(tag => {
      const tagEl = createStyledTag(tag, {
        removable: true,
        onRemove: removeTag
      });
      currentTagsContainer.appendChild(tagEl);
    });
  }

  if (localTags.length > 0) {
    const localLabel = document.createElement('div');
    localLabel.style.cssText = 'color: #888; font-size: 12px; margin-top: 10px; margin-bottom: 5px; width: 100%;';
    localLabel.textContent = 'Local Filter Tags (Applied in browser)';
    currentTagsContainer.appendChild(localLabel);
    
    localTags.forEach(tag => {
      const tagEl = createStyledTag(tag, {
        removable: true,
        onRemove: removeTag
      });
      tagEl.style.opacity = '0.7';
      currentTagsContainer.appendChild(tagEl);
    });
  }
}

// === FILTERING ===
function filterPostLocally(post, localFilterTags) {
  if (!post || !post.tag_string) return true;
  const postTags = post.tag_string.split(' ');

  if (CONFIG.DENYLIST.some(deniedTag => postTags.includes(deniedTag))) {
    return true;
  }

  if (!localFilterTags.every(localTag => postTags.includes(localTag))) {
    return true;
  }

  return false;
}

// === LOAD POSTS ===
async function loadPosts(append = false) {
  if (state.isLoading) return;
  
  state.isLoading = true;
  loadingIndicator.style.display = 'block';
  errorContainer.innerHTML = '';
  
  const apiTags = state.currentTags.slice(0, 2);
  const localFilterTags = state.currentTags.slice(2);

  try {
    const postsFromApi = await searchPosts(apiTags, state.currentPage);
    const posts = postsFromApi.filter(post => !filterPostLocally(post, localFilterTags));
    
    if (!append) {
      state.posts = [];
      gallery.innerHTML = '';
    }
    
    if (posts && posts.length > 0) {
      state.posts = state.posts.concat(posts);
      
      for (const post of posts) {
        await displayPost(post);
      }
      
      if (postsFromApi.length > 0) {
        state.lastPostId = postsFromApi[postsFromApi.length - 1].id;
      }
      
      if (postsFromApi.length === CONFIG.POSTS_PER_PAGE) {
        loadMoreContainer.style.display = 'block';
      } else {
        loadMoreContainer.style.display = 'none';
      }

      if (posts.length === 0 && !append) {
        gallery.innerHTML = `<div class="loading">No posts found (filtered by local tags)</div>`;
      }
    } else {
      if (!append) {
        gallery.innerHTML = '<div class="loading">No posts found</div>';
      }
      loadMoreContainer.style.display = 'none';
    }
  } catch (err) {
    console.error('Error loading posts:', err);
    errorContainer.innerHTML = `
      <div style="background: rgba(255,50,50,0.1); border: 1px solid #ff3232; padding: 15px; border-radius: 6px; color: #ff6b6b;">
        Error loading posts: ${escapeHtml(err.message)}
      </div>
    `;
    loadMoreContainer.style.display = 'none';
  } finally {
    state.isLoading = false;
    loadingIndicator.style.display = 'none';
  }
}

async function displayPost(post) {
  const item = document.createElement('div');
  item.className = 'gallery-item';
  
  const imageUrl = `proxy.php?url=${encodeURIComponent(post.large_file_url || post.preview_file_url || post.file_url)}`;
  
  // Get vote counts
  let votes = {upvotes: 0, downvotes: 0, score: 0};
  try {
    const voteRes = await fetch(`votes.php?action=get&post_id=${post.id}`);
    const voteData = await voteRes.json();
    votes = {
      upvotes: voteData.upvotes || 0,
      downvotes: voteData.downvotes || 0,
      score: voteData.score || 0
    };
  } catch (err) {
    console.error('Error loading votes:', err);
  }
  
  item.innerHTML = `
    <img src="${imageUrl}" alt="Post ${post.id}" loading="lazy" />
    <div class="gallery-item-info">
      <div class="gallery-item-id">Post #${post.id}</div>
      <div class="gallery-item-votes">
        <span class="vote-display up">▲ ${votes.upvotes}</span>
        <span class="vote-display down">▼ ${votes.downvotes}</span>
        <span style="color: #4a9eff;">Score: ${votes.score}</span>
      </div>
    </div>
  `;
  
  item.addEventListener('click', () => openModal(post));
  gallery.appendChild(item);
}

// === MODAL ===
async function openModal(post) {
  state.currentPost = post;
  const fullImageUrl = `proxy.php?url=${encodeURIComponent(post.large_file_url || post.file_url)}`;
  
  // Get votes
  let votes = {upvotes: 0, downvotes: 0, score: 0};
  let userVote = 0;
  
  try {
    const voteRes = await fetch(`votes.php?action=get&post_id=${post.id}`);
    const voteData = await voteRes.json();
    votes = {
      upvotes: voteData.upvotes || 0,
      downvotes: voteData.downvotes || 0,
      score: voteData.score || 0
    };
    
    const userVoteRes = await fetch(`votes.php?action=user_vote&post_id=${post.id}&session_id=${sessionId}`);
    const userVoteData = await userVoteRes.json();
    userVote = userVoteData.vote || 0;
  } catch (err) {
    console.error('Error loading votes:', err);
  }
  
  // Get comments
  let comments = [];
  try {
    const commentsRes = await fetch(`comments.php?action=get&post_id=${post.id}&session_id=${sessionId}`);
    const commentsData = await commentsRes.json();
    comments = commentsData.comments || [];
  } catch (err) {
    console.error('Error loading comments:', err);
  }
  
  modalContent.innerHTML = `
    <img src="${fullImageUrl}" alt="Post ${post.id}" />
    <div class="modal-info" id="modalInfo">
      <div style="margin-bottom: 15px;">
        <strong style="color: #4a9eff; font-size: 18px;">Post #${post.id}</strong>
        <span style="color: #888; margin-left: 15px;">
          ${post.image_width}×${post.image_height} | ${post.file_ext ? post.file_ext.toUpperCase() : 'N/A'}
        </span>
      </div>
      
      <div class="modal-votes">
        <button class="vote-btn upvote ${userVote === 1 ? 'active' : ''}" data-vote="1">
          <i class="fas fa-arrow-up"></i> Upvote (${votes.upvotes})
        </button>
        <button class="vote-btn downvote ${userVote === -1 ? 'active' : ''}" data-vote="-1">
          <i class="fas fa-arrow-down"></i> Downvote (${votes.downvotes})
        </button>
        <span style="color: #4a9eff; padding: 8px 15px;">Score: ${votes.score}</span>
      </div>
      
      <div style="margin: 15px 0;">
        <strong style="color: #aaa;">Tags:</strong>
        <div id="modalTags" style="margin-top: 8px;"></div>
      </div>
      
      ${post.source ? `
        <div style="margin-top: 10px;">
          <a href="${escapeHtml(post.source)}" target="_blank" style="color: #4a9eff;">View Source</a>
        </div>
      ` : ''}
      
      <div class="comments-section">
        <div class="comments-header">Comments (${comments.length})</div>
        <div class="comment-form">
          <textarea class="comment-input" id="commentInput" placeholder="Write a comment..." maxlength="2000"></textarea>
          <button class="comment-submit" id="commentSubmit"><i class="fas fa-paper-plane"></i> Post</button>
        </div>
        <div id="commentsList"></div>
      </div>
    </div>
  `;
  
  // Stop propagation on the entire modal-info section
  const modalInfo = document.getElementById('modalInfo');
  modalInfo.addEventListener('click', (e) => {
    e.stopPropagation();
  });
  
  // Render colored tags
  const tagsContainer = document.getElementById('modalTags');
  if (post.tag_string) {
    const tags = post.tag_string.split(' ').filter(t => t);
    tags.forEach(tag => {
      const tagEl = createStyledTag(tag, {
        clickable: true,
        removable: false
      });
      
      // Add click handler to add tag to search
      tagEl.style.cursor = 'pointer';
      tagEl.addEventListener('click', (e) => {
        // Stop event from bubbling
        e.stopPropagation();
        
        // Visual feedback
        tagEl.style.transform = 'scale(0.95)';
        setTimeout(() => {
          tagEl.style.transform = 'scale(1)';
        }, 100);
        
        // Add tag to search after brief delay
        setTimeout(() => {
          // Close modal
          closeModal();
          
          // Add tag to search
          if (!state.currentTags.includes(tag)) {
            addTag(tag);
          } else {
            // Tag already in search, just show browse screen
            if (!browseScreen.classList.contains('active')) {
              welcomeScreen.classList.add('hidden');
              browseScreen.classList.add('active');
            }
          }
        }, 150);
      });
      
      tagsContainer.appendChild(tagEl);
    });
  }
  
  // Render comments
  renderComments(comments);
  
  // Setup vote buttons - stop propagation to prevent modal close
  document.querySelectorAll('.vote-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      e.preventDefault();
      await handleVote(btn.dataset.vote);
    });
  });
  
  // Setup comment submit - stop propagation
  const commentSubmitBtn = document.getElementById('commentSubmit');
  const commentInput = document.getElementById('commentInput');
  
  commentSubmitBtn.addEventListener('click', async (e) => {
    e.stopPropagation();
    e.preventDefault();
    await postComment();
  });
  
  // Also handle Enter key in textarea
  commentInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      e.stopPropagation();
      postComment();
    }
  });
  
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function renderComments(comments) {
  const commentsList = document.getElementById('commentsList');
  commentsList.innerHTML = '';
  
  if (!comments || comments.length === 0) {
    return;
  }
  
  comments.forEach(comment => {
    const commentEl = document.createElement('div');
    commentEl.className = 'comment';
    
    const date = new Date(comment.created_at).toLocaleString();
    
    commentEl.innerHTML = `
      <div class="comment-header">
        <span class="comment-author">${escapeHtml(comment.username)}</span>
        <span class="comment-date">${date}</span>
      </div>
      <div class="comment-text">${escapeHtml(comment.comment)}</div>
    `;
    
    commentsList.appendChild(commentEl);
  });
}

async function handleVote(voteValue) {
  if (!state.currentPost) return;
  
  try {
    const formData = new FormData();
    formData.append('action', 'vote');
    formData.append('session_id', sessionId);
    formData.append('post_id', state.currentPost.id);
    formData.append('vote', voteValue);
    
    const response = await fetch('votes.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Reload votes without closing modal
      const voteRes = await fetch(`votes.php?action=get&post_id=${state.currentPost.id}`);
      const voteData = await voteRes.json();
      const votes = {
        upvotes: voteData.upvotes || 0,
        downvotes: voteData.downvotes || 0,
        score: voteData.score || 0
      };
      
      const userVoteRes = await fetch(`votes.php?action=user_vote&post_id=${state.currentPost.id}&session_id=${sessionId}`);
      const userVoteData = await userVoteRes.json();
      const userVote = userVoteData.vote || 0;
      
      // Update vote buttons
      document.querySelectorAll('.vote-btn').forEach(btn => {
        const btnVote = parseInt(btn.dataset.vote);
        btn.classList.toggle('active', btnVote === userVote);
        
        if (btnVote === 1) {
          btn.innerHTML = `<i class="fas fa-arrow-up"></i> Upvote (${votes.upvotes})`;
        } else {
          btn.innerHTML = `<i class="fas fa-arrow-down"></i> Downvote (${votes.downvotes})`;
        }
      });
      
      // Update score display
      const scoreDisplay = document.querySelector('.modal-votes span');
      if (scoreDisplay) {
        scoreDisplay.textContent = `Score: ${votes.score}`;
      }
    } else {
      alert('Failed to cast vote');
    }
  } catch (err) {
    console.error('Vote error:', err);
    alert('Failed to cast vote');
  }
}

async function postComment() {
  const commentInput = document.getElementById('commentInput');
  if (!commentInput) return;
  
  const comment = commentInput.value.trim();
  
  if (!comment) return;
  
  try {
    const formData = new FormData();
    formData.append('action', 'post');
    formData.append('session_id', sessionId);
    formData.append('post_id', state.currentPost.id);
    formData.append('comment', comment);
    
    const response = await fetch('comments.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      commentInput.value = '';
      
      // Update points display
      if (currentUser) {
        currentUser.points += data.points_earned;
        document.getElementById('userPoints').textContent = currentUser.points;
      }
      
      // Reload comments without closing modal
      const commentsRes = await fetch(`comments.php?action=get&post_id=${state.currentPost.id}&session_id=${sessionId}`);
      const commentsData = await commentsRes.json();
      const comments = commentsData.comments || [];
      
      // Update comment count
      const commentHeader = document.querySelector('.comments-header');
      if (commentHeader) {
        commentHeader.textContent = `Comments (${comments.length})`;
      }
      
      // Re-render comments
      renderComments(comments);
    } else {
      alert(data.error || 'Failed to post comment');
    }
  } catch (err) {
    console.error('Comment error:', err);
    alert('Failed to post comment');
  }
}

function closeModal() {
  modal.classList.remove('active');
  document.body.style.overflow = '';
  state.currentPost = null;
}

// === EVENT LISTENERS ===
initialSearchBtn.addEventListener('click', performInitialSearch);
headerSearchBtn.addEventListener('click', () => {
  addTag(headerSearch.value.trim().replace(/\s+/g, '_'));
  headerSearch.value = '';
});

loadMoreBtn.addEventListener('click', () => {
  state.currentPage++;
  loadPosts(true);
});

modalClose.addEventListener('click', closeModal);

modal.addEventListener('click', (e) => {
  if (e.target === modal) {
    closeModal();
  }
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && modal.classList.contains('active')) {
    closeModal();
  }
});

// === UTILITY ===
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}