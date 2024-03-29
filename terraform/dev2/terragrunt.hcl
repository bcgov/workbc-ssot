terraform {
  source = "./"
}

include {
  path = find_in_parent_folders()
}

locals {
  project = get_env("LICENSE_PLATE")
}

generate "dev2_tfvars" {
  path              = "dev2.auto.tfvars"
  if_exists         = "overwrite"
  disable_signature = true
  contents          = <<-EOF
    alb_name = "default"
    cloudfront = true
    cloudfront_origin_domain = "workbc-ssot.${local.project}-dev2.nimbus.cloud.gov.bc.ca"
    service_names = ["workbc-ssot"]
  EOF
}
