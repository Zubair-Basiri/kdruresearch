<template>
  <div class="table-card">
    <div class="table-card-header">
      <span>{{ title }}</span>
      <select v-if="filter" class="mini-select">
        <option v-for="year in years" :key="year">{{ year }}</option>
      </select>
    </div>

    <div class="table-card-body">
      <table>
        <thead>
          <tr>
            <th v-for="col in columns" :key="col">{{ col }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.label">
            <td>{{ row.label }}</td>
            <td v-for="col in columns.slice(1)" :key="col" :style="cellStyle(row[col])">
              {{ row[col] }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

defineProps({
  title: String,
  columns: { type: Array, default: () => [] }, // e.g. ["Researcher", "Publications", "Indexed"]
  rows: { type: Array, default: () => [] },     // e.g. [{ label: "R1", Publications: 12, Indexed: 5 }]
  filter: { type: Boolean, default: false },
  years: { type: Array, default: () => [] }     // for filter dropdown
})

// Dynamic coloring based on value (heatmap style)
function cellStyle(value){
  if(value===undefined || value===null) return { textAlign:'center', fontWeight:'600' }
  const intensity = Math.min(value/100,1)        // simple intensity ratio
  const red = Math.floor(255 * intensity)
  const green = Math.floor(200 + (55*(1-intensity)))
  const blue = 50
  return { backgroundColor:`rgb(${red},${green},${blue})`, textAlign:'center', fontWeight:'600', borderRadius:'6px', padding:'4px', color:'#000' }
}
</script>

<style scoped>
.table-card{
  background:#fff;
  border-radius:14px;
  padding:10px;
  box-shadow:0 15px 40px rgba(0,0,0,.08);
  min-height:200px;
}
.table-card-header{
  display:flex;
  justify-content:space-between;
  font-weight:600;
  font-size:14px;
  margin-bottom:6px;
}
.mini-select{
  font-size:12px;
  padding:2px 6px;
}
.table-card-body{
  overflow:auto;
  max-height:180px;
}
table{
  width:100%;
  border-collapse:separate;
  border-spacing:0 4px;
}
th{
  text-align:left;
  font-weight:700;
  font-size:13px;
  padding:6px;
  color:#0f172a;
}
td{
  font-size:13px;
  padding:6px;
}
tr:hover td{
  transform:scale(1.02);
  transition:all 0.2s ease;
}
</style>
