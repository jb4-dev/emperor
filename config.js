// Configuration file for Emperor Browser with Dual API Support

const CONFIG = {
  // API Configuration - Multiple sources
  APIS: {
    'pgn': {
      name: 'PGN API',
      base_url: 'https://',
      proxy: 'api_proxy.php',
      endpoints: {
        posts: '/posts.json',
        autocomplete: '/autocomplete.json',
        tags: '/tags.json'
      }
    },
    'jb4': {
      name: 'JB4 API',
      base_url: 'https://',
      proxy: 'api_proxy_jb4.php',
      endpoints: {
        posts: '/index.php?page=dapi&s=post&q=index',
        autocomplete: '/autocomplete.php',
        tags: '/index.php?page=dapi&s=tag&q=index'
      },
      format: 'xml' // JB4 uses XML by default
    }
  },
  
  // Default API (will be overridden by user settings)
  DEFAULT_API: 'pgn',
  
  // Get current API from user settings or default
  getCurrentAPI() {
    const userAPI = localStorage.getItem('preferred_api');
    return userAPI || this.DEFAULT_API;
  },
  
  // Get API configuration
  getAPIConfig(apiName) {
    return this.APIS[apiName || this.getCurrentAPI()];
  },
  
  // Default rating tag (user cannot change this)
  DEFAULT_RATING_TAG: 'rating:explicit', // Options: rating:general, rating:safe, rating:questionable, rating:explicit
  
  // Default deny-list of tags (these will be excluded from all searches)
  DENYLIST: [
    'gore',
    'scat',
    'vore',
    'fart',
    'watersports',
    'diaper',
    'inflation',
    'hyper',
    'cub',
    'young',
    'loli',
    'shota',
    'toddlercon',
    'baby',
    'infant',
    'futanari',
    'gay',
    'femboy',
    '1futa',
    'furry',
    'comic',
    'ai_generated',
    'male_focus',
    'pee',
    'peeing',
    'poop',
    'blood',
    'pool_of_blood',
    'bestiality',
    'animal_penis',
    'dildo',
    'ai-generated',
    'amputation',
    'milking',
    'torture',
    'rape',
    'lactation',
    'slave',
    'pregnant'
  ],
  
  // Posts per page (API max is 200 for PGN, 1000 for JB4)
  POSTS_PER_PAGE: 50,
  
  // Autocomplete settings
  AUTOCOMPLETE_LIMIT: 10,
  AUTOCOMPLETE_DEBOUNCE_MS: 300,
  
  // Sort options
  SORT_OPTIONS: {
    'date': 'Date (Newest First)',
    'popularity': 'Popularity (Most Upvoted)',
    'score': 'Score (Highest Rated)'
  },
  
  DEFAULT_SORT: 'date'
};

// Create the denylist query string
CONFIG.DENYLIST_QUERY = CONFIG.DENYLIST.map(tag => `-${tag}`).join(' ');