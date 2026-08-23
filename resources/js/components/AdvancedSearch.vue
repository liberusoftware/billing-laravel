

<template>
  <div class="advanced-search">
    <div class="search-form">
      <div class="search-fields">
        <div class="field">
          <input 
            v-model="searchCriteria.content" 
            @input="handleInput"
            placeholder="Search content..." 
            type="text"
          >
          <div v-if="suggestions.length" class="suggestions">
            <div 
              v-for="suggestion in suggestions" 
              :key="suggestion.id"
              @click="selectSuggestion(suggestion)"
              class="suggestion-item"
            >
              {{ suggestion.content }}
            </div>
          </div>
        </div>
        
        <div class="field">
          <select v-model="searchCriteria.dateRange">
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="custom">Custom Range</option>
          </select>
        </div>

        <div v-if="searchCriteria.dateRange === 'custom'" class="date-range">
          <input type="date" v-model="searchCriteria.startDate">
          <input type="date" v-model="searchCriteria.endDate">
        </div>
      </div>

      <div class="search-actions">
        <button @click="search" class="search-btn">Search</button>
        <button @click="saveSearch" class="save-btn">Save Search</button>
        <button @click="shareSearch" class="share-btn">Share Search</button>
      </div>
    </div>

    <div class="saved-searches" v-if="savedSearches.length">
      <h4>Saved Searches</h4>
      <div v-for="saved in savedSearches" :key="saved.id" class="saved-search">
        <span>{{ saved.name }}</span>
        <button @click="loadSavedSearch(saved)">Load</button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue'
import debounce from 'lodash/debounce'

export default {
  name: 'AdvancedSearch',
  
  emits: ['search'],

  setup(props, { emit }) {
    const searchCriteria = reactive({
      content: '',
      dateRange: 'month',
      startDate: null,
      endDate: null
    })

    const suggestions = ref([])
    const savedSearches = ref([])

    const handleInput = debounce(async () => {
      if (searchCriteria.content.length < 2) {
        suggestions.value = []
        return
      }
      
      const response = await axios.get('/api/search-suggestions', {
        params: { query: searchCriteria.content }
      })
      suggestions.value = response.data
    }, 300)

    const search = () => {
      emit('search', searchCriteria)
    }

    const saveSearch = async () => {
      const name = prompt('Enter a name for this search:')
      if (!name) return

      await axios.post('/api/saved-searches', {
        name,
        criteria: searchCriteria
      })
      
      await loadSavedSearches()
    }

    const shareSearch = async () => {
      const response = await axios.post('/api/shared-searches', {
        criteria: searchCriteria
      })
      
      const shareUrl = `${window.location.origin}/search/${response.data.token}`
      alert(`Share this URL: ${shareUrl}`)
    }

    const loadSavedSearches = async () => {
      const response = await axios.get('/api/saved-searches')
      savedSearches.value = response.data
    }

    const loadSavedSearch = (saved) => {
      Object.assign(searchCriteria, saved.criteria)
      search()
    }

    return {
      searchCriteria,
      suggestions,
      savedSearches,
      handleInput,
      search,
      saveSearch,
      shareSearch,
      loadSavedSearch
    }
  }
}
</script>

<style scoped>
.advanced-search {
  padding: 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.search-fields {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.suggestions {
  position: absolute;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  max-height: 200px;
  overflow-y: auto;
}

.suggestion-item {
  padding: 0.5rem;
  cursor: pointer;
}

.suggestion-item:hover {
  background: #f5f5f5;
}
</style>