<template>
  <div class="holiday-calendar">
    <!-- En-tête calendrier -->
    <div class="calendar-header">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h5 class="mb-0">Calendrier des congés - {{ currentYear }}</h5>
          <small class="text-muted">
            {{ totalEvents }} congé(s) programmé(s)
          </small>
        </div>
        <div class="btn-group">
          <button
            class="btn btn-sm btn-outline-primary"
            @click="previousMonth"
            :disabled="loading"
          >
            <i class="fas fa-chevron-left"></i>
          </button>
          <button
            class="btn btn-sm btn-outline-primary"
            @click="todayView"
            :disabled="loading"
          >
            Aujourd'hui
          </button>
          <button
            class="btn btn-sm btn-outline-primary"
            @click="nextMonth"
            :disabled="loading"
          >
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- Légende -->
      <div class="calendar-legend">
        <span class="legend-item">
          <span class="legend-color bg-primary"></span>
          Congé annuel
        </span>
        <span class="legend-item">
          <span class="legend-color bg-danger"></span>
          Congé maladie
        </span>
        <span class="legend-item">
          <span class="legend-color bg-warning"></span>
          Congé d'urgence
        </span>
        <span class="legend-item">
          <span class="legend-color bg-info"></span>
          Formation
        </span>
        <span class="legend-item">
          <span class="legend-color bg-success"></span>
          Autres
        </span>
      </div>
    </div>

    <!-- Calendrier -->
    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2 text-muted">Chargement du calendrier...</p>
    </div>

    <div v-else class="calendar-grid">
      <!-- En-têtes jours -->
      <div class="calendar-weekdays">
        <div
          v-for="day in weekdays"
          :key="day"
          class="calendar-weekday"
        >
          {{ day }}
        </div>
      </div>

      <!-- Grille du calendrier -->
      <div class="calendar-days">
        <div
          v-for="date in calendarDates"
          :key="date.key"
          class="calendar-day"
          :class="{
            'other-month': date.isOtherMonth,
            'today': date.isToday,
            'has-events': date.events.length > 0
          }"
        >
          <div class="day-number">{{ date.day }}</div>

          <!-- Événements du jour -->
          <div class="day-events">
            <div
              v-for="event in date.events.slice(0, 3)"
              :key="event.id"
              class="calendar-event"
              :class="getEventClass(event)"
              :title="getEventTooltip(event)"
              @click="showEventDetails(event)"
            >
              <div class="event-title">{{ event.agent }}</div>
              <div v-if="event.jours > 1" class="event-duration">
                {{ event.jours }}j
              </div>
            </div>

            <!-- Plus d'événements -->
            <div
              v-if="date.events.length > 3"
              class="more-events"
              @click="showDayDetails(date)"
            >
              +{{ date.events.length - 3 }} autres
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal détails jour -->
    <div
      v-if="showDayModal"
      class="modal fade show"
      style="display: block; background: rgba(0,0,0,0.5);"
      @click="closeDayModal"
    >
      <div class="modal-dialog" @click.stop>
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              Congés du {{ selectedDate?.format('DD/MM/YYYY') }}
            </h5>
            <button
              type="button"
              class="btn-close"
              @click="closeDayModal"
            ></button>
          </div>
          <div class="modal-body">
            <div v-if="selectedDate?.events.length === 0" class="text-center text-muted py-3">
              Aucun congé ce jour
            </div>
            <div v-else>
              <div
                v-for="event in selectedDate?.events"
                :key="event.id"
                class="holiday-item mb-3 p-3 border rounded"
              >
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <h6 class="mb-1">{{ event.agent }}</h6>
                    <span
                      class="badge"
                      :class="getStatusBadgeClass(event.type)"
                    >
                      {{ event.type }}
                    </span>
                    <div class="text-muted mt-1">
                      <small>
                        <i class="fas fa-calendar me-1"></i>
                        {{ event.start }} → {{ event.end }}
                        ({{ event.jours }} jours)
                      </small>
                    </div>
                    <div v-if="event.structure" class="text-muted">
                      <small>
                        <i class="fas fa-building me-1"></i>
                        {{ event.structure }}
                      </small>
                    </div>
                  </div>
                  <div class="event-color" :class="getEventClass(event)"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { format, startOfMonth, endOfMonth, startOfWeek, endOfWeek, eachDayOfInterval,
         isSameMonth, isToday, addMonths, subMonths, parseISO } from 'date-fns'
import { fr } from 'date-fns/locale'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'

const props = defineProps({
  filters: {
    type: Object,
    required: true
  }
})

const ui = useUiStore()

// État
const loading = ref(false)
const currentDate = ref(new Date())
const events = ref([])
const showDayModal = ref(false)
const selectedDate = ref(null)

const weekdays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

// Computed
const currentYear = computed(() => currentDate.value.getFullYear())

const monthStart = computed(() => startOfMonth(currentDate.value))
const monthEnd = computed(() => endOfMonth(currentDate.value))

const calendarStart = computed(() => startOfWeek(monthStart.value, { weekStartsOn: 1 }))
const calendarEnd = computed(() => endOfWeek(monthEnd.value, { weekStartsOn: 1 }))

const calendarDates = computed(() => {
  const dates = eachDayOfInterval({ start: calendarStart.value, end: calendarEnd.value })

  return dates.map(date => {
    const dateStr = format(date, 'yyyy-MM-dd')
    const dayEvents = events.value.filter(event => {
      const eventStart = parseISO(event.start)
      const eventEnd = parseISO(event.end)
      return date >= eventStart && date <= eventEnd
    })

    return {
      key: dateStr,
      date,
      day: date.getDate(),
      isOtherMonth: !isSameMonth(date, currentDate.value),
      isToday: isToday(date),
      events: dayEvents
    }
  })
})

const totalEvents = computed(() => events.value.length)

// Méthodes
async function loadCalendarData() {
  loading.value = true
  try {
    const params = {
      year: currentDate.value.getFullYear(),
      month: currentDate.value.getMonth() + 1,
      ...props.filters
    }

    const response = await client.get('/holiday-plannings/calendar', { params })
    events.value = response.data.events || []
  } catch (error) {
    console.error('Erreur chargement calendrier:', error)
    ui.addToast('Erreur lors du chargement du calendrier', 'danger')
  } finally {
    loading.value = false
  }
}

function previousMonth() {
  currentDate.value = subMonths(currentDate.value, 1)
  loadCalendarData()
}

function nextMonth() {
  currentDate.value = addMonths(currentDate.value, 1)
  loadCalendarData()
}

function todayView() {
  currentDate.value = new Date()
  loadCalendarData()
}

function getEventClass(event) {
  const typeClasses = {
    'maladie': 'event-maladie',
    'urgence': 'event-urgence',
    'formation': 'event-formation',
    'annuel': 'event-annuel'
  }
  return typeClasses[event.extendedProps?.type] || 'event-other'
}

function getEventTooltip(event) {
  return `${event.agent}\n${event.extendedProps?.type}\n${event.extendedProps?.jours} jour(s)\n${event.extendedProps?.structure || ''}`
}

function getStatusBadgeClass(type) {
  const classes = {
    'maladie': 'bg-danger',
    'urgence': 'bg-warning',
    'formation': 'bg-info',
    'annuel': 'bg-primary'
  }
  return classes[type] || 'bg-secondary'
}

function showEventDetails(event) {
  // Rediriger vers les détails du congé
  // router.push({ name: 'rh.holidays.show', params: { id: event.id } })
}

function showDayDetails(date) {
  selectedDate.value = date
  showDayModal.value = true
}

function closeDayModal() {
  showDayModal.value = false
  selectedDate.value = null
}

// Watchers
watch(() => props.filters, () => {
  loadCalendarData()
}, { deep: true })

// Initialisation
onMounted(() => {
  loadCalendarData()
})
</script>

<style scoped>
.holiday-calendar {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
}

.calendar-header {
  border-bottom: 1px solid #e9ecef;
  padding-bottom: 1rem;
  margin-bottom: 1.5rem;
}

.calendar-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 1rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.legend-color {
  width: 12px;
  height: 12px;
  border-radius: 2px;
}

.calendar-grid {
  overflow: hidden;
}

.calendar-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 1px;
  background: #e9ecef;
}

.calendar-weekday {
  background: #f8f9fa;
  padding: 0.75rem;
  text-align: center;
  font-weight: 600;
  font-size: 0.875rem;
  color: #495057;
}

.calendar-days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 1px;
  background: #e9ecef;
}

.calendar-day {
  background: white;
  min-height: 120px;
  padding: 0.5rem;
  position: relative;
  border: 1px solid transparent;
  transition: all 0.2s;
}

.calendar-day:hover {
  background: #f8f9fa;
}

.calendar-day.today {
  background: #e3f2fd;
  border-color: #2196f3;
}

.calendar-day.other-month {
  background: #fafafa;
  color: #999;
}

.calendar-day.has-events {
  background: #fff3cd;
}

.day-number {
  font-weight: 600;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
}

.day-events {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.calendar-event {
  padding: 2px 4px;
  border-radius: 3px;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 20px;
}

.calendar-event:hover {
  transform: scale(1.02);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.event-annuel {
  background: #bbdefb;
  color: #1976d2;
}

.event-maladie {
  background: #ffcdd2;
  color: #d32f2f;
}

.event-urgence {
  background: #fff3c4;
  color: #f57c00;
}

.event-formation {
  background: #b3e5fc;
  color: #0288d1;
}

.event-other {
  background: #c8e6c9;
  color: #388e3c;
}

.event-title {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.event-duration {
  font-size: 0.65rem;
  opacity: 0.8;
}

.more-events {
  font-size: 0.7rem;
  color: #666;
  cursor: pointer;
  padding: 1px 4px;
  text-align: center;
  background: #e9ecef;
  border-radius: 2px;
}

.more-events:hover {
  background: #dee2e6;
}

.holiday-item {
  background: #f8f9fa;
}

.event-color {
  width: 20px;
  height: 20px;
  border-radius: 4px;
}

/* Responsive */
@media (max-width: 768px) {
  .calendar-day {
    min-height: 80px;
    padding: 0.25rem;
  }

  .day-number {
    font-size: 0.75rem;
  }

  .calendar-event {
    font-size: 0.65rem;
    min-height: 16px;
  }

  .calendar-legend {
    gap: 0.5rem;
  }

  .legend-item {
    font-size: 0.75rem;
  }
}
</style>