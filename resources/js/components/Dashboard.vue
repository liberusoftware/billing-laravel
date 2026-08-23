

<template>
  <div class="dashboard">
    <div class="dashboard-header">
      <h2>Analytics Dashboard</h2>
      <button @click="refreshData" class="refresh-btn">
        Refresh Data
      </button>
    </div>

    <div class="charts-grid" :style="gridLayout">
      <div v-for="(chart, index) in activeCharts" 
           :key="index" 
           class="chart-container">
        <canvas :ref="'chart-' + index"></canvas>
      </div>
    </div>

    <div class="dashboard-settings">
      <h3>Customize Dashboard</h3>
      <div class="chart-toggles">
        <label v-for="(chart, name) in availableCharts" :key="name">
          <input type="checkbox" 
                 v-model="chart.active"
                 @change="savePreferences">
          {{ chart.title }}
        </label>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import Chart from 'chart.js/auto'

export default {
  name: 'Dashboard',
  
  setup() {
    const charts = ref([])
    const availableCharts = ref({
      revenue: {
        title: 'Revenue Overview',
        active: true,
        type: 'line'
      },
      invoices: {
        title: 'Invoice Status',
        active: true,
        type: 'pie'
      },
      clients: {
        title: 'Active Clients',
        active: true,
        type: 'bar'
      }
    })

    const refreshInterval = ref(null)

    const fetchData = async () => {
      const response = await axios.get('/api/dashboard/metrics')
      updateCharts(response.data)
    }

    const updateCharts = (data) => {
      charts.value.forEach(chart => chart.destroy())
      charts.value = []

      Object.entries(availableCharts.value).forEach(([key, config], index) => {
        if (!config.active) return

        const ctx = document.getElementById('chart-' + index)
        const chartData = data[key]
        
        charts.value.push(new Chart(ctx, {
          type: config.type,
          data: chartData,
          options: {
            responsive: true,
            interaction: {
              mode: 'index',
              intersect: false,
            }
          }
        }))
      })
    }

    const savePreferences = async () => {
      await axios.post('/api/dashboard/preferences', {
        charts: availableCharts.value
      })
    }

    onMounted(async () => {
      const { data } = await axios.get('/api/dashboard/preferences')
      availableCharts.value = data.charts || availableCharts.value
      
      await fetchData()
      refreshInterval.value = setInterval(fetchData, 60000)
    })

    onUnmounted(() => {
      if (refreshInterval.value) {
        clearInterval(refreshInterval.value)
      }
    })

    return {
      availableCharts,
      refreshData: fetchData,
      savePreferences
    }
  }
}
</script>

<style scoped>
.dashboard {
  padding: 20px;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  margin: 20px 0;
}

.chart-container {
  background: white;
  padding: 15px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>