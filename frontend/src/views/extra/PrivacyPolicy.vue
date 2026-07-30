<template>
  <div class="container-fluid py-4 policy-page">

    <!-- Header -->
    <div class="policy-header text-center">
      <div class="policy-icon">
        <i class="fas fa-book-open"></i>
      </div>
      <h2>Research Database Policy</h2>
      <p class="subtitle">
        <strong>Kandahar University</strong><br>
        Vice Chancellery of Academic Research
      </p>
    </div>

    <div class="row">
      <div class="col-lg-10 mx-auto">

        <div
          class="policy-card"
          v-for="(item,index) in policies"
          :key="index"
        >
          <div class="card-number">
            {{ index + 1 }}
          </div>

          <div class="policy-content">
            <h4>{{ item.title }}</h4>
            <p v-html="item.content"></p>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>

<script>
export default {
  name: "PrivacyPolicy",

  data() {
    return {
      policies: [
        {
          title: "Purpose",
          content:
            "The Research Database is the official repository for managing, preserving, and disseminating the University's research outputs."
        },
        {
          title: "Scope",
          content:
            "This policy applies to all academic staff, researchers, faculties, departments, and authorized users."
        },
        {
          title: "User Responsibilities",
          content:
            "• Submit accurate information.<br>• Update research records.<br>• Protect account credentials.<br>• Follow research ethics."
        },
        {
          title: "Data Quality",
          content:
            "All records must be accurate, complete, verifiable, and approved before publication."
        },
        {
          title: "Access Control",
          content:
            "Access is role-based. Unauthorized viewing, editing, or deletion of records is prohibited."
        },
        {
          title: "Privacy & Security",
          content:
            "The University protects research information through authentication, encryption, backups, and monitoring."
        },
        {
          title: "Research Integrity",
          content:
            "Users shall ensure originality, proper citation, and compliance with ethical research standards."
        },
        {
          title: "Reporting",
          content:
            "The system provides reliable reports to support planning, accreditation, and institutional decision-making."
        },
        {
          title: "Compliance",
          content:
            "Violations may result in suspension of access or disciplinary action under University regulations."
        },
        {
          title: "Policy Review",
          content:
            "This policy shall be reviewed periodically to ensure continuous improvement and compliance."
        }
      ]
    };
  }
};
</script>

<style scoped>

.policy-page{
    background:#f5f7fb;
    min-height:100vh;
}

/* Header */

.policy-header{
    padding:40px 20px;
}

.policy-icon{
    width:80px;
    height:80px;
    margin:auto;
    background:linear-gradient(135deg,#0d6efd,#2b8cff);
    color:white;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:34px;
    box-shadow:0 12px 30px rgba(13,110,253,.3);
}

.policy-header h2{
    margin-top:20px;
    font-weight:700;
    color:#17324d;
}

.subtitle{
    color:#0edd64;
    font-size:17px;
}

/* Cards */

.policy-card{
    background:white;
    border-radius:18px;
    padding:28px;
    margin-bottom:22px;
    display:flex;
    align-items:flex-start;
    box-shadow:0 10px 35px rgba(0,0,0,.06);
    transition:.3s;
    border-left:6px solid #0d6efd;
}

.policy-card:hover{
    transform:translateY(-5px);
    box-shadow:0 18px 40px rgba(0,0,0,.12);
}

.card-number{
    min-width:55px;
    height:55px;
    border-radius:50%;
    background:#0d6efd;
    color:white;
    font-weight:700;
    font-size:20px;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-right:22px;
}

.policy-content h4{
    color:#17324d;
    font-weight:600;
    margin-bottom:12px;
}

.policy-content p{
    color:#555;
    line-height:1.9;
    margin:0;
}

/* Mobile */

@media(max-width:768px){

.policy-card{
    flex-direction:column;
}

.card-number{
    margin-bottom:15px;
}

}

</style>