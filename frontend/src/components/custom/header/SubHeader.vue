<template>
  <div class="iq-navbar-header">
    <b-container fluid class="iq-container">
      <b-row>
        <b-col md="12">
          <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
              <h1>Hello Dear!</h1>
              <p>Experience a simple and powerful Database for Research.</p>
            </div>
          </div>
        </b-col>
      </b-row>
    </b-container>
  </div>
</template>

<style scoped>
.iq-navbar-header {
  background: var(--subheader-bg);
  position: relative;
  transition: background 0.3s ease;
  border-radius: 0 0 20px 20px;
  margin-bottom: 20px;
}
.iq-container {
  position: relative;
  z-index: 2;
  padding: 2rem 1.5rem;
}
.iq-navbar-header h1 {
  font-size: 1.8rem;
  font-weight: 600;
  color: white;
}
.iq-navbar-header p {
  opacity: 0.9;
  margin-bottom: 0;
  color: white;
}
</style>