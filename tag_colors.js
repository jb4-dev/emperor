// Tag color-coding system using tags_clean.json

const TAG_COLORS = {
  0: '#95afc0',     // general - Gray-blue
  4: '#4ecdc4',     // character - Teal
  3: '#a569bd',     // copyright - Purple
  1: '#ff6b6b',     // artist - Red
  5: '#f39c12',     // meta - Orange
  6: '#777777'      // faulty - Gray
};

const CATEGORY_NAMES = {
  0: 'general',
  4: 'character',
  3: 'copyright',
  1: 'artist',
  5: 'meta',
  6: 'faulty'
};

// Tag lookup objects
let tagsByName = {};
let tagsById = {};
let tagsLoaded = false;

/**
 * Load tags from tags_clean.json
 */
async function loadTagsDatabase() {
  if (tagsLoaded) return true;
  
  try {
    const response = await fetch('tags_clean.json');
    if (!response.ok) {
      console.error('Failed to load tags_clean.json');
      return false;
    }
    
    const data = await response.json();
    
    // Build lookup indexes
    for (const [categoryStr, tagList] of Object.entries(data)) {
      const category = parseInt(categoryStr);
      
      if (!Array.isArray(tagList)) continue;
      
      for (const tag of tagList) {
        if (!tag.name || !tag.id) continue;
        
        const tagData = {
          id: tag.id,
          name: tag.name,
          post_count: tag.post_count || 0,
          category: category
        };
        
        // Index by name (lowercase for case-insensitive lookup)
        tagsByName[tag.name.toLowerCase()] = tagData;
        
        // Index by ID
        tagsById[tag.id] = tagData;
      }
    }
    
    tagsLoaded = true;
    console.log(`Loaded ${Object.keys(tagsByName).length} tags from database`);
    return true;
  } catch (err) {
    console.error('Error loading tags database:', err);
    return false;
  }
}

/**
 * Get tag information by name
 * @param {string} tagName - The tag name
 * @returns {object|null} Tag data or null
 */
function getTagInfo(tagName) {
  if (!tagsLoaded) {
    console.warn('Tags database not loaded yet');
    return null;
  }
  
  const normalized = tagName.toLowerCase().replace(/\s+/g, '_');
  return tagsByName[normalized] || null;
}

/**
 * Get tag information by ID
 * @param {number} tagId - The tag ID
 * @returns {object|null} Tag data or null
 */
function getTagInfoById(tagId) {
  if (!tagsLoaded) {
    console.warn('Tags database not loaded yet');
    return null;
  }
  
  return tagsById[tagId] || null;
}

/**
 * Get the color for a tag
 * @param {string} tagName - The tag name
 * @returns {string} Hex color code
 */
function getTagColor(tagName) {
  const tagInfo = getTagInfo(tagName);
  
  if (tagInfo && tagInfo.category !== undefined) {
    return TAG_COLORS[tagInfo.category] || TAG_COLORS[0];
  }
  
  // Default to general
  return TAG_COLORS[0];
}

/**
 * Get the category for a tag
 * @param {string} tagName - The tag name
 * @returns {number} Category number (0-6)
 */
function getTagCategory(tagName) {
  const tagInfo = getTagInfo(tagName);
  return tagInfo ? tagInfo.category : 0;
}

/**
 * Get the post count for a tag
 * @param {string} tagName - The tag name
 * @returns {number} Post count
 */
function getTagPostCount(tagName) {
  const tagInfo = getTagInfo(tagName);
  return tagInfo ? tagInfo.post_count : 0;
}

/**
 * Search tags by prefix
 * @param {string} prefix - Search prefix
 * @param {number} limit - Maximum results
 * @returns {Array} Array of matching tag objects
 */
function searchTags(prefix, limit = 10) {
  if (!tagsLoaded) return [];
  
  const normalized = prefix.toLowerCase();
  const results = [];
  
  for (const [name, tagData] of Object.entries(tagsByName)) {
    if (name.startsWith(normalized)) {
      results.push(tagData);
      if (results.length >= limit) break;
    }
  }
  
  // Sort by post count (descending)
  results.sort((a, b) => b.post_count - a.post_count);
  
  return results.slice(0, limit);
}

/**
 * Create a styled tag element
 * @param {string} tag - The tag text
 * @param {object} options - Options for the tag element
 * @returns {HTMLElement} The tag element
 */
function createStyledTag(tag, options = {}) {
  const {
    removable = false,
    onRemove = null,
    clickable = true
  } = options;
  
  const tagInfo = getTagInfo(tag);
  const color = getTagColor(tag);
  const categoryName = tagInfo ? CATEGORY_NAMES[tagInfo.category] : 'general';
  
  const tagEl = document.createElement('span');
  tagEl.className = 'colored-tag';
  tagEl.title = `${categoryName} tag` + (tagInfo ? ` (${tagInfo.post_count.toLocaleString()} posts)` : '');
  tagEl.style.cssText = `
    background: ${color}22;
    border: 1px solid ${color};
    color: ${color};
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 2px;
    cursor: ${clickable || removable ? 'pointer' : 'default'};
    transition: all 0.2s ease;
  `;
  
  // Add hover effect
  if (clickable || removable) {
    tagEl.addEventListener('mouseenter', () => {
      tagEl.style.background = `${color}44`;
      if (!removable) tagEl.style.transform = 'translateY(-1px)';
    });
    tagEl.addEventListener('mouseleave', () => {
      tagEl.style.background = `${color}22`;
      if (!removable) tagEl.style.transform = 'translateY(0)';
    });
  }
  
  // Category indicator (small dot)
  const dot = document.createElement('span');
  dot.style.cssText = `
    width: 6px;
    height: 6px;
    background: ${color};
    border-radius: 50%;
    display: inline-block;
  `;
  tagEl.appendChild(dot);
  
  // Tag text
  const textSpan = document.createElement('span');
  textSpan.textContent = tag.replace(/_/g, ' ');
  tagEl.appendChild(textSpan);
  
  // Remove button
  if (removable && onRemove) {
    const removeBtn = document.createElement('span');
    removeBtn.innerHTML = '×';
    removeBtn.style.cssText = `
      cursor: pointer;
      font-weight: bold;
      font-size: 16px;
      line-height: 1;
      margin-left: 2px;
      padding: 0 4px;
    `;
    removeBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      onRemove(tag);
    });
    tagEl.appendChild(removeBtn);
  }
  
  return tagEl;
}

/**
 * Parse and color-code a tag string
 * @param {string} tagString - Space-separated tags
 * @param {object} options - Options for rendering
 * @returns {HTMLElement} Container with all tags
 */
function renderColoredTags(tagString, options = {}) {
  const container = document.createElement('div');
  container.style.cssText = 'display: flex; flex-wrap: wrap; gap: 4px; align-items: center;';
  
  if (!tagString) return container;
  
  const tags = tagString.split(' ').filter(t => t);
  
  tags.forEach(tag => {
    const tagEl = createStyledTag(tag, options);
    container.appendChild(tagEl);
  });
  
  return container;
}

// Auto-load tags database when script loads
loadTagsDatabase();