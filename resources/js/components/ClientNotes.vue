

<template>
  <div class="client-notes">
    <div class="notes-header">
      <h3>Internal Notes</h3>
      <div class="search-box">
        <input 
          v-model="search" 
          @input="debounceSearch"
          placeholder="Search notes..." 
          type="text"
        >
      </div>
    </div>

    <div class="note-form">
      <textarea 
        v-model="newNote" 
        placeholder="Add a new note..."
        rows="3"
      ></textarea>
      <button @click="addNote" :disabled="!newNote.trim()">
        Add Note
      </button>
    </div>

    <div class="notes-list">
      <div v-for="note in notes" :key="note.id" class="note-item">
        <div class="note-content">{{ note.content }}</div>
        <div class="note-meta">
          <span>By {{ note.user.name }}</span>
          <span>{{ formatDate(note.created_at) }}</span>
          <button @click="deleteNote(note.id)" class="delete-btn">
            Delete
          </button>
        </div>
      </div>
      
      <div v-if="notes.length === 0" class="no-notes">
        No notes found
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import debounce from 'lodash/debounce'

export default {
  name: 'ClientNotes',
  
  props: {
    clientId: {
      type: Number,
      required: true
    }
  },

  setup(props) {
    const notes = ref([])
    const newNote = ref('')
    const search = ref('')

    const fetchNotes = async () => {
      const response = await axios.get('/api/client-notes', {
        params: {
          client_id: props.clientId,
          search: search.value
        }
      })
      notes.value = response.data.data
    }

    const addNote = async () => {
      await axios.post('/api/client-notes', {
        client_id: props.clientId,
        content: newNote.value
      })
      newNote.value = ''
      await fetchNotes()
    }

    const deleteNote = async (noteId) => {
      await axios.delete(`/api/client-notes/${noteId}`)
      await fetchNotes()
    }

    const debounceSearch = debounce(() => {
      fetchNotes()
    }, 300)

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }

    onMounted(() => {
      fetchNotes()
    })

    return {
      notes,
      newNote,
      search,
      addNote,
      deleteNote,
      debounceSearch,
      formatDate
    }
  }
}
</script>

<style scoped>
.client-notes {
  padding: 20px;
}

.notes-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.note-form {
  margin-bottom: 20px;
}

.note-item {
  border: 1px solid #ddd;
  padding: 10px;
  margin-bottom: 10px;
  border-radius: 4px;
}

.note-meta {
  display: flex;
  justify-content: space-between;
  font-size: 0.9em;
  color: #666;
  margin-top: 8px;
}
</style>