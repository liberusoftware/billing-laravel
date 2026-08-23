

<template>
  <div class="invoice-status-tracker">
    <div class="status-timeline">
      <div 
        v-for="(status, index) in statusSteps" 
        :key="index"
        class="status-step"
        :class="{ 'completed': isCompleted(status.key) }"
      >
        <div class="status-icon">
          <i :class="status.icon"></i>
        </div>
        <div class="status-details">
          <h4>{{ status.label }}</h4>
          <p v-if="getStatusTimestamp(status.key)">
            {{ formatDate(getStatusTimestamp(status.key)) }}
          </p>
        </div>
      </div>
    </div>
    
    <div class="status-history" v-if="invoice.status_history">
      <h3>Status History</h3>
      <ul>
        <li v-for="(event, index) in invoice.status_history" :key="index">
          {{ event.status }} - {{ formatDate(event.timestamp) }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import Echo from 'laravel-echo'

export default {
  props: {
    invoice: {
      type: Object,
      required: true
    }
  },
  
  setup(props) {
    const statusSteps = ref([
      { key: 'sent', label: 'Sent', icon: 'fas fa-paper-plane' },
      { key: 'viewed', label: 'Viewed', icon: 'fas fa-eye' },
      { key: 'paid', label: 'Paid', icon: 'fas fa-check-circle' }
    ])

    const isCompleted = (status) => {
      const timestamp = getStatusTimestamp(status)
      return timestamp !== null
    }

    const getStatusTimestamp = (status) => {
      switch(status) {
        case 'sent': return props.invoice.sent_at
        case 'viewed': return props.invoice.viewed_at
        case 'paid': return props.invoice.paid_at
        default: return null
      }
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleString()
    }

    onMounted(() => {
      window.Echo.private(`invoices.${props.invoice.id}`)
        .listen('InvoiceStatusChanged', (e) => {
          // Update the invoice status in real-time
          props.invoice[`${e.status}_at`] = e.timestamp
        })
    })

    return {
      statusSteps,
      isCompleted,
      getStatusTimestamp,
      formatDate
    }
  }
}
</script>

<style scoped>
.invoice-status-tracker {
  padding: 1.5rem;
}

.status-timeline {
  display: flex;
  justify-content: space-between;
  margin-bottom: 2rem;
}

.status-step {
  display: flex;
  align-items: center;
  opacity: 0.5;
}

.status-step.completed {
  opacity: 1;
}

.status-icon {
  margin-right: 1rem;
  font-size: 1.5rem;
}

.status-history {
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid #eee;
}
</style>