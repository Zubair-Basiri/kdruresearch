import api from './api'

const responseData = result => result.status === 'fulfilled' ? result.value.data : null
const asArray = value => Array.isArray(value) ? value : (Array.isArray(value?.data) ? value.data : [])

/**
 * Load the dropdown data shared by analytics pages.
 *
 * Each request settles independently so one optional endpoint cannot blank all
 * dropdowns on a page. Failed request names are returned for diagnostics.
 */
export async function loadAnalyticsFilters(options = {}) {
  const endpoints = {
    faculties: '/faculties',
    departments: '/departments',
    researchers: '/lecturers-for-dropdown',
    metadata: '/key-findings/filters',
  }

  if (options.universities) endpoints.universities = '/universities'
  if (options.collaborationTypes) endpoints.collaborationTypes = '/collaboration-types'
  if (options.researchAreas) endpoints.researchAreas = '/research-areas'

  const names = Object.keys(endpoints)
  const settled = await Promise.allSettled(names.map(name => api.get(endpoints[name])))
  const values = {}
  const failures = []

  settled.forEach((result, index) => {
    const name = names[index]
    values[name] = responseData(result)
    if (result.status === 'rejected') {
      failures.push(name)
      console.error(`Failed to load analytics filter: ${name}`, result.reason)
    }
  })

  return {
    faculties: asArray(values.faculties),
    departments: asArray(values.departments),
    researchers: asArray(values.researchers),
    metadata: values.metadata || {},
    universities: asArray(values.universities),
    collaborationTypes: asArray(values.collaborationTypes),
    researchAreas: asArray(values.researchAreas),
    failures,
  }
}
