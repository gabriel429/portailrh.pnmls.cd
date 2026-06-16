<template>
  <div class="holiday-calendar">
    <div class="calendar-topbar">
      <div>
        <h5 class="mb-1">{{ monthLabel }}</h5>
        <div class="calendar-subtitle">
          {{ monthEvents.length }} congé(s), {{ monthDays }} jour(s)
        </div>
      </div>
      <div class="calendar-actions">
        <button class="icon-btn" type="button" title="Mois précédent" :disabled="loading" @click="previousMonth">
          <i class="fas fa-chevron-left"></i>
        </button>
        <button class="today-btn" type="button" :disabled="loading" @click="todayView">
          Aujourd'hui
        </button>
        <button class="icon-btn" type="button" title="Mois suivant" :disabled="loading" @click="nextMonth">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <div class="calendar-toolbar">
      <div class="calendar-search">
        <i class="fas fa-search"></i>
        <input v-model="search" type="search" class="form-control" placeholder="Agent, structure">
      </div>
      <select v-model="typeFilter" class="form-select">
        <option value="">Tous les types</option>
        <option value="annuel">Annuel</option>
        <option value="maladie">Maladie</option>
        <option value="maternite">Maternité</option>
        <option value="paternite">Paternité</option>
        <option value="urgence">Urgence</option>
        <option value="special">Spécial</option>
      </select>
    </div>

    <div class="calendar-metrics">
      <div class="metric">
        <span>{{ activeTodayCount }}</span>
        <small>En cours</small>
      </div>
      <div class="metric">
        <span>{{ pendingCount }}</span>
        <small>En attente</small>
      </div>
      <div class="metric">
        <span>{{ annualDays }}</span>
        <small>Jours annuels</small>
      </div>
    </div>

    <div v-if="loading" class="calendar-loading">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div v-else class="calendar-workspace">
      <div class="calendar-board">
        <div class="calendar-weekdays">
          <div v-for="day in weekdays" :key="day" class="calendar-weekday">{{ day }}</div>
        </div>

        <div class="calendar-days">
          <button
            v-for="date in calendarDates"
            :key="date.key"
            type="button"
            class="calendar-day"
            :class="{
              'other-month': date.isOtherMonth,
              today: date.isToday,
              selected: selectedDate?.key === date.key,
              busy: date.events.length > 0
            }"
            @click="selectDay(date)"
          >
            <span class="day-number">{{ date.day }}</span>
            <span v-if="date.events.length" class="day-count">{{ date.events.length }}</span>

            <span class="day-events">
              <span
                v-for="event in date.events.slice(0, 3)"
                :key="event.id"
                class="calendar-event"
                :class="[typeClass(event.type_conge), statusClass(event.statut_demande)]"
                @click.stop="selectHoliday(event)"
              >
                <span>{{ event.agent }}</span>
              </span>
              <span v-if="date.events.length > 3" class="more-events">+{{ date.events.length - 3 }}</span>
            </span>
          </button>
        </div>
      </div>

      <aside class="day-panel">
        <div class="day-panel-header">
          <div>
            <div class="panel-label">Sélection</div>
            <h6>{{ selectedDayLabel }}</h6>
          </div>
          <span class="badge bg-primary">{{ selectedDayEvents.length }}</span>
        </div>

        <div v-if="selectedDayEvents.length === 0" class="empty-day">
          Aucun congé ce jour
        </div>

        <div v-else class="day-holiday-list">
          <article v-for="event in selectedDayEvents" :key="event.id" class="day-holiday">
            <div class="holiday-line">
              <span class="type-dot" :class="typeClass(event.type_conge)"></span>
              <div>
                <strong>{{ event.agent }}</strong>
                <small>{{ event.type_label }} · {{ event.jours }}j</small>
              </div>
            </div>
            <div class="holiday-dates">
              {{ formatDate(event.start) }} → {{ formatDate(event.end) }}
            </div>
            <div class="holiday-footer">
              <span class="badge" :class="statusBadgeClass(event.statut_demande)">
                {{ event.statut_label }}
              </span>
              <button type="button" class="btn btn-sm btn-outline-primary" @click="selectHoliday(event)">
                Modifier
              </button>
            </div>
          </article>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import {
  addMonths,
  endOfMonth,
  endOfWeek,
  eachDayOfInterval,
  format,
  isSameMonth,
  isToday,
  parseISO,
  startOfMonth,
  startOfWeek,
  subMonths
} from 'date-fns'
import { fr } from 'date-fns/locale'
import client from '@/api/client'
import { useUiStore } from '@/stores/ui'

const props = defineProps({
  filters: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['holiday-selected'])
const ui = useUiStore()

const loading = ref(false)
const currentDate = ref(new Date())
const events = ref([])
const search = ref('')
const typeFilter = ref('')
const selectedDate = ref(null)

const weekdays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

const monthLabel = computed(() => {
  return format(currentDate.value, 'MMMM yyyy', { locale: fr })
    .replace(/^\w/, c => c.toUpperCase())
})

const monthStart = computed(() => startOfMonth(currentDate.value))
const monthEnd = computed(() => endOfMonth(currentDate.value))
const calendarStart = computed(() => startOfWeek(monthStart.value, { weekStartsOn: 1 }))
const calendarEnd = computed(() => endOfWeek(monthEnd.value, { weekStartsOn: 1 }))

const filteredEvents = computed(() => {
  const query = search.value.trim().toLowerCase()

  return events.value.filter(event => {
    const matchesType = !typeFilter.value || event.type_conge === typeFilter.value
    const matchesSearch = !query || [
      event.agent,
      event.structure,
      event.type_label,
      event.statut_label
    ].some(value => String(value || '').toLowerCase().includes(query))

    return matchesType && matchesSearch
  })
})

const monthEvents = computed(() => {
  return filteredEvents.value.filter(event => rangesOverlap(parseISO(event.start), parseISO(event.end), monthStart.value, monthEnd.value))
})

const monthDays = computed(() => monthEvents.value.reduce((total, event) => total + (Number(event.jours) || 0), 0))
const pendingCount = computed(() => monthEvents.value.filter(event => event.statut_demande === 'en_attente').length)
const annualDays = computed(() => monthEvents.value.filter(event => event.type_conge === 'annuel').reduce((total, event) => total + (Number(event.jours) || 0), 0))
const activeTodayCount = computed(() => {
  const today = new Date()
  return filteredEvents.value.filter(event => event.statut_demande === 'approuve' && dateInRange(today, parseISO(event.start), parseISO(event.end))).length
})

const calendarDates = computed(() => {
  return eachDayOfInterval({ start: calendarStart.value, end: calendarEnd.value }).map(date => {
    const key = format(date, 'yyyy-MM-dd')
    const dayEvents = filteredEvents.value.filter(event => dateInRange(date, parseISO(event.start), parseISO(event.end)))

    return {
      key,
      date,
      day: date.getDate(),
      isOtherMonth: !isSameMonth(date, currentDate.value),
      isToday: isToday(date),
      events: dayEvents
    }
  })
})

const selectedDayEvents = computed(() => selectedDate.value?.events || [])
const selectedDayLabel = computed(() => selectedDate.value ? format(selectedDate.value.date, 'dd MMMM yyyy', { locale: fr }) : 'Aucun jour')

function rangesOverlap(startA, endA, startB, endB) {
  return startA <= endB && endA >= startB
}

function dateInRange(date, start, end) {
  const current = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  const normalizedStart = new Date(start.getFullYear(), start.getMonth(), start.getDate())
  const normalizedEnd = new Date(end.getFullYear(), end.getMonth(), end.getDate())
  return current >= normalizedStart && current <= normalizedEnd
}

async function loadCalendarData() {
  loading.value = true
  try {
    const params = {
      ...props.filters,
      year: currentDate.value.getFullYear(),
      month: currentDate.value.getMonth() + 1
    }

    const response = await client.get('/holiday-plannings/calendar', { params })
    events.value = response.data.events || []
    selectedDate.value = calendarDates.value.find(day => day.isToday) || calendarDates.value.find(day => day.events.length > 0) || calendarDates.value[0]
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

function selectDay(date) {
  selectedDate.value = date
}

function selectHoliday(event) {
  emit('holiday-selected', event)
}

function typeClass(type) {
  return {
    annuel: 'type-annuel',
    maladie: 'type-maladie',
    maternite: 'type-maternite',
    paternite: 'type-paternite',
    urgence: 'type-urgence',
    special: 'type-special'
  }[type] || 'type-special'
}

function statusClass(status) {
  return status === 'en_attente' ? 'is-pending' : ''
}

function statusBadgeClass(status) {
  return {
    approuve: 'bg-success',
    en_attente: 'bg-warning text-dark',
    refuse: 'bg-danger',
    annule: 'bg-secondary'
  }[status] || 'bg-secondary'
}

function formatDate(value) {
  if (!value) return '-'
  return format(parseISO(value), 'dd/MM/yyyy')
}

watch(() => props.filters, () => loadCalendarData(), { deep: true })

watch([search, typeFilter], () => {
  const selectedKey = selectedDate.value?.key
  selectedDate.value = calendarDates.value.find(day => day.key === selectedKey)
    || calendarDates.value.find(day => day.events.length > 0)
    || calendarDates.value[0]
    || null
})

onMounted(() => {
  loadCalendarData()
})
</script>

<style scoped>
.holiday-calendar {
  background: #fff;
  border: 1px solid #d9e9f7;
  border-radius: 14px;
  padding: 1rem;
}

.calendar-topbar,
.calendar-toolbar,
.calendar-metrics {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.calendar-subtitle,
.panel-label {
  color: #64748b;
  font-size: .85rem;
}

.calendar-actions {
  display: inline-flex;
  gap: .35rem;
}

.icon-btn,
.today-btn {
  min-height: 36px;
  border: 1px solid #b8dff6;
  border-radius: 8px;
  background: #fff;
  color: #0369a1;
  padding: 0 .75rem;
}

.calendar-toolbar {
  justify-content: flex-start;
}

.calendar-search {
  position: relative;
  flex: 1;
  min-width: 220px;
}

.calendar-search i {
  position: absolute;
  left: .75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
}

.calendar-search .form-control {
  padding-left: 2rem;
}

.calendar-toolbar .form-select {
  width: 190px;
}

.calendar-metrics {
  justify-content: flex-start;
}

.metric {
  min-width: 118px;
  border: 1px solid #e2edf6;
  border-radius: 10px;
  padding: .75rem;
  background: #f8fbfd;
}

.metric span {
  display: block;
  color: #0f172a;
  font-size: 1.35rem;
  font-weight: 800;
}

.metric small {
  color: #64748b;
}

.calendar-loading {
  display: grid;
  min-height: 360px;
  place-items: center;
}

.calendar-workspace {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  gap: 1rem;
}

.calendar-board {
  min-width: 0;
  overflow: hidden;
  border: 1px solid #d9e9f7;
  border-radius: 12px;
}

.calendar-weekdays,
.calendar-days {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
}

.calendar-weekday {
  background: #f0f7fc;
  color: #31516f;
  font-size: .75rem;
  font-weight: 700;
  padding: .7rem .5rem;
  text-align: center;
}

.calendar-day {
  min-height: 118px;
  border: 0;
  border-top: 1px solid #e3eef7;
  border-right: 1px solid #e3eef7;
  background: #fff;
  padding: .5rem;
  text-align: left;
  transition: background .15s ease, box-shadow .15s ease;
}

.calendar-day:nth-child(7n) {
  border-right: 0;
}

.calendar-day:hover,
.calendar-day.selected {
  background: #eef8ff;
  box-shadow: inset 0 0 0 2px #7dd3fc;
}

.calendar-day.other-month {
  background: #fbfdff;
  color: #94a3b8;
}

.calendar-day.today .day-number {
  background: #0284c7;
  color: #fff;
}

.day-number {
  display: inline-grid;
  width: 28px;
  height: 28px;
  place-items: center;
  border-radius: 50%;
  font-weight: 800;
}

.day-count {
  float: right;
  min-width: 22px;
  border-radius: 999px;
  background: #e0f2fe;
  color: #0369a1;
  font-size: .72rem;
  font-weight: 700;
  text-align: center;
}

.day-events {
  display: grid;
  gap: .25rem;
  margin-top: .45rem;
}

.calendar-event {
  display: block;
  overflow: hidden;
  border-radius: 6px;
  padding: .25rem .45rem;
  color: #0f172a;
  font-size: .72rem;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.calendar-event.is-pending {
  border: 1px dashed rgba(15, 23, 42, .35);
}

.more-events {
  color: #0369a1;
  font-size: .72rem;
  font-weight: 700;
}

.day-panel {
  border: 1px solid #d9e9f7;
  border-radius: 12px;
  background: #f8fbfd;
  min-width: 0;
  padding: 1rem;
}

.day-panel-header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.day-panel-header h6 {
  margin: 0;
  color: #0f172a;
  font-weight: 800;
}

.empty-day {
  display: grid;
  min-height: 160px;
  place-items: center;
  color: #64748b;
}

.day-holiday-list {
  display: grid;
  gap: .75rem;
}

.day-holiday {
  border: 1px solid #e2edf6;
  border-radius: 10px;
  background: #fff;
  padding: .75rem;
}

.holiday-line {
  display: flex;
  gap: .65rem;
  align-items: flex-start;
}

.holiday-line strong,
.holiday-line small {
  display: block;
}

.holiday-line small,
.holiday-dates {
  color: #64748b;
  font-size: .8rem;
}

.type-dot {
  width: 12px;
  height: 12px;
  flex: 0 0 12px;
  border-radius: 50%;
  margin-top: .3rem;
}

.holiday-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  margin-top: .75rem;
}

.type-annuel { background: #bfdbfe; }
.type-maladie { background: #fecaca; }
.type-maternite { background: #bbf7d0; }
.type-paternite { background: #bae6fd; }
.type-urgence { background: #fde68a; }
.type-special { background: #ddd6fe; }

@media (max-width: 991.98px) {
  .calendar-workspace {
    grid-template-columns: 1fr;
  }

  .day-panel {
    order: -1;
  }
}

@media (max-width: 575.98px) {
  .calendar-topbar,
  .calendar-toolbar,
  .calendar-metrics {
    align-items: stretch;
    flex-direction: column;
  }

  .calendar-toolbar .form-select {
    width: 100%;
  }

  .calendar-day {
    min-height: 92px;
    padding: .35rem;
  }

  .calendar-event {
    font-size: .65rem;
  }
}
</style>
