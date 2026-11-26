--
-- Grant additional permissions to special users.
--
GRANT SELECT,INSERT,UPDATE ON TABLE public.sources TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE ON TABLE public.monthly_labour_market_updates TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.industry_outlook TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.labour_force_survey_industry TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.labour_force_survey_regional_employment TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.labour_force_survey_regional_industry_region TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.labour_force_survey_regional_industry_province TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.career_provincial TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.career_regional TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.career_transition_opportunities TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.high_opportunity_occupations TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.openings_careers TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.openings_industry TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.regional_labour_market_outlook TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.regional_top_occupations TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.skills TO ssot_lmmu;
GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE public.wages TO ssot_lmmu;
