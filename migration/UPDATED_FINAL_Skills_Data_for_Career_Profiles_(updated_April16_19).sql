alter table t_0 rename "NOC" to noc;
alter table t_0 rename "Skills_Competencies" to skills_competencies;
alter table t_0 rename "Importance" to importance;
alter table t_0 rename "Importance_Description" to importance_description;
alter table t_0 rename "Proficiency" to proficiency;
alter table t_0 rename "Proficiency Description" to proficiency_description;
create index noc_index on t_0(noc);
alter table t_0 rename to skills;
